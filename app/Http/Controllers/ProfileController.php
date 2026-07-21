<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRatingRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Offer;
use App\Models\Rating;
use App\Models\User;
use App\Notifications\NewRatingNotification;
use App\Notifications\UserFollowedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Get user profile with statistics.
     * Email is intentionally excluded — only exposed in personalInfo() for the owner.
     */
    public function show($userId)
    {
        $user = User::withCount(['followers', 'following'])
            ->withAvg('receivedRatings', 'rating')
            ->withCount('receivedRatings')
            ->findOrFail($userId);

        $currentUser = Auth::user();

        $isFollowing = $currentUser
            ? $currentUser->following()->where('following_id', $userId)->exists()
            : false;

        // Check if the viewed user is following back the current user
        $isFollowingBack = $currentUser
            ? $user->following()->where('following_id', $currentUser->user_id)->exists()
            : false;

        return response()->json([
            'success' => true,
            'message' => __('User profile retrieved successfully'),
            'data' => [
                'user_id' => $user->user_id,
                'username' => $user->username,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'followers_count' => $user->followers_count,
                'following_count' => $user->following_count,
                'average_rating' => round($user->received_ratings_avg_rating ?? 0, 2),
                'total_ratings' => $user->received_ratings_count,
                'is_following' => $isFollowing,
                'is_following_back' => $isFollowingBack,
            ],
        ], 200);
    }

    /**
     * Follow a user
     */
    public function follow(Request $request, $userId)
    {
        $currentUser = Auth::user();

        if ($currentUser->user_id === $userId) {
            return response()->json([
                'success' => false,
                'message' => __('You cannot follow yourself'),
            ], 400);
        }

        $followedUser = User::findOrFail($userId);

        if ($currentUser->following()->where('following_id', $userId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('You are already following this user'),
            ], 400);
        }

        $currentUser->following()->attach($userId);

        // Send notification to the followed user
        $followedUser->notify(new UserFollowedNotification($currentUser));

        return response()->json([
            'success' => true,
            'message' => __('Successfully followed user'),
        ], 200);
    }

    /**
     * Unfollow a user
     */
    public function unfollow(Request $request, $userId)
    {
        $currentUser = Auth::user();

        if ($currentUser->user_id === $userId) {
            return response()->json([
                'success' => false,
                'message' => __('You cannot unfollow yourself'),
            ], 400);
        }

        User::findOrFail($userId);

        if (! $currentUser->following()->where('following_id', $userId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('You are not following this user'),
            ], 400);
        }

        $currentUser->following()->detach($userId);

        return response()->json([
            'success' => true,
            'message' => __('Successfully unfollowed user'),
        ], 200);
    }

    /**
     * Get list of followers for a user.
     *
     * Per item returns:
     *   - is_following     : apakah current user follow user ini
     *   - is_following_back: apakah follower ini juga follow balik profile user (-> tombol "Follow Back")
     *   - mutual_friends   : maks 3 PP orang yang sama-sama di-follow current user & follower ini
     *   - mutual_count     : total jumlah mutual
     */
    public function followers($userId)
    {
        $user = User::findOrFail($userId);
        $currentUser = Auth::user();

        $followers = $user->followers()
            ->select('users.user_id', 'users.username', 'users.name', 'users.avatar')
            ->get();

        // ID semua yang di-follow profile user — untuk cek is_following_back
        $profileUserFollowingIds = $user->following()->pluck('users.user_id')->flip();

        // ID semua yang di-follow current user — di-load sekali di luar loop
        $currentUserFollowingIds = $currentUser
            ? $currentUser->following()->pluck('users.user_id')->flip()
            : collect();

        $result = $followers->map(function ($follower) use (
            $currentUser,
            $currentUserFollowingIds,
            $profileUserFollowingIds
        ) {
            // Apakah current user sudah follow follower ini?
            $isFollowing = $currentUser && $currentUser->user_id !== $follower->user_id
                ? $currentUserFollowingIds->has($follower->user_id)
                : false;

            // Apakah follower ini juga follow balik profile user? (-> "Follow Back" button)
            $isFollowingBack = $profileUserFollowingIds->has($follower->user_id);

            // Mutual: irisan antara following current user & following milik follower ini
            $mutualFriends = [];
            $mutualCount = 0;
            if ($currentUser && $currentUser->user_id !== $follower->user_id) {
                $followerFollowingIds = $follower->following()
                    ->pluck('users.user_id')
                    ->toArray();

                $mutuals = $currentUser->following()
                    ->whereIn('users.user_id', $followerFollowingIds)
                    ->select('users.user_id', 'users.username', 'users.name', 'users.avatar')
                    ->get();

                $mutualCount = $mutuals->count();
                $mutualFriends = $mutuals->take(3)->values()->toArray();
            }

            return [
                'user_id' => $follower->user_id,
                'username' => $follower->username,
                'name' => $follower->name,
                'avatar' => $follower->avatar,
                'is_following' => $isFollowing,
                'is_following_back' => $isFollowingBack,
                'mutual_friends' => $mutualFriends,
                'mutual_count' => $mutualCount,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => __('Followers retrieved successfully'),
            'data' => $result,
        ], 200);
    }

    /**
     * Get list of users that a user is following.
     * Same response shape as followers().
     */
    public function following($userId)
    {
        $user = User::findOrFail($userId);
        $currentUser = Auth::user();

        $following = $user->following()
            ->select('users.user_id', 'users.username', 'users.name', 'users.avatar')
            ->get();

        // ID semua followers profile user — untuk cek is_following_back
        $profileUserFollowerIds = $user->followers()->pluck('users.user_id')->flip();

        $currentUserFollowingIds = $currentUser
            ? $currentUser->following()->pluck('users.user_id')->flip()
            : collect();

        $result = $following->map(function ($followedUser) use (
            $currentUser,
            $currentUserFollowingIds,
            $profileUserFollowerIds
        ) {
            $isFollowing = $currentUser && $currentUser->user_id !== $followedUser->user_id
                ? $currentUserFollowingIds->has($followedUser->user_id)
                : false;

            // Apakah orang yang di-follow ini juga follow balik profile user?
            $isFollowingBack = $profileUserFollowerIds->has($followedUser->user_id);

            $mutualFriends = [];
            $mutualCount = 0;
            if ($currentUser && $currentUser->user_id !== $followedUser->user_id) {
                $followedUserFollowingIds = $followedUser->following()
                    ->pluck('users.user_id')
                    ->toArray();

                $mutuals = $currentUser->following()
                    ->whereIn('users.user_id', $followedUserFollowingIds)
                    ->select('users.user_id', 'users.username', 'users.name', 'users.avatar')
                    ->get();

                $mutualCount = $mutuals->count();
                $mutualFriends = $mutuals->take(3)->values()->toArray();
            }

            return [
                'user_id' => $followedUser->user_id,
                'username' => $followedUser->username,
                'name' => $followedUser->name,
                'avatar' => $followedUser->avatar,
                'is_following' => $isFollowing,
                'is_following_back' => $isFollowingBack,
                'mutual_friends' => $mutualFriends,
                'mutual_count' => $mutualCount,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => __('Following retrieved successfully'),
            'data' => $result,
        ], 200);
    }

    /**
     * Get rating breakdown for a user.
     * Returns average, total count, and per-star count + percentage (5 down to 1).
     */
    public function ratingBreakdown($userId)
    {
        $user = User::findOrFail($userId);

        $totalRatings = $user->receivedRatings()->count();
        $averageRating = $user->receivedRatings()->avg('rating') ?? 0;

        $ratingCounts = $user->receivedRatings()
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $breakdown = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = $ratingCounts[$i] ?? 0;
            $percentage = $totalRatings > 0 ? round(($count / $totalRatings) * 100, 1) : 0;
            $breakdown[] = ['stars' => $i, 'count' => $count, 'percentage' => $percentage];
        }

        return response()->json([
            'success' => true,
            'message' => __('Rating breakdown retrieved successfully'),
            'data' => [
                'total_ratings' => $totalRatings,
                'average_rating' => round($averageRating, 2),
                'breakdown' => $breakdown,
            ],
        ], 200);
    }

    /**
     * Add or update a rating for a user.
     * One user can only rate another user once — subsequent calls update the existing rating.
     */
    public function addRating(StoreRatingRequest $request, $userId)
    {
        $currentUser = Auth::user();

        if ($currentUser->user_id === $userId) {
            return response()->json([
                'success' => false,
                'message' => __('You cannot rate yourself'),
            ], 400);
        }

        User::findOrFail($userId);

        $existingRating = Rating::where('rater_id', $currentUser->user_id)
            ->where('rated_user_id', $userId)
            ->where('offer_id', $request->offer_id)
            ->first();

        if ($existingRating) {
            $existingRating->update($request->validated());
            $offer = Offer::find($request->offer_id);
            if ($offer) {
                $ratedUser = User::find($userId);
                $ratedUser->notify(new NewRatingNotification($currentUser, $request->rating, $offer));
            }

            return response()->json([
                'success' => true,
                'message' => __('Rating updated successfully'),
                'data' => $existingRating,
            ], 200);
        }

        $rating = Rating::create([
            'rater_id' => $currentUser->user_id,
            'rated_user_id' => $userId,
            ...$request->validated(),
        ]);

        $offer = Offer::find($request->offer_id);
        if ($offer) {
            $ratedUser = User::find($userId);
            $ratedUser->notify(new NewRatingNotification($currentUser, $request->rating, $offer));
        }

        return response()->json([
            'success' => true,
            'message' => __('Rating added successfully'),
            'data' => $rating,
        ], 201);
    }

    /**
     * Update own username and/or avatar URL.
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $currentUser = Auth::user();
        $currentUser->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => __('Profile updated successfully'),
            'data' => [
                'user_id' => $currentUser->user_id,
                'username' => $currentUser->username,
                'name' => $currentUser->name,
                'email' => $currentUser->email,
                'avatar' => $currentUser->avatar,
                'created_at' => $currentUser->created_at->format('F j, Y, g:i A'),
            ],
        ], 200);
    }

    /**
     * Get personal information for the account settings page (own profile only).
     * This is the only endpoint that exposes email and created_at.
     */
    public function personalInfo()
    {
        $currentUser = Auth::user();

        return response()->json([
            'success' => true,
            'message' => __('Personal information retrieved successfully'),
            'data' => [
                'user_id' => $currentUser->user_id,
                'username' => $currentUser->username,
                'name' => $currentUser->name,
                'email' => $currentUser->email,
                'avatar' => $currentUser->avatar,
                'created_at' => $currentUser->created_at->format('F j, Y, g:i A'),
            ],
        ], 200);
    }
}
