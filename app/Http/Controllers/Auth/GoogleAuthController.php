<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect(): JsonResponse|RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function callback(): JsonResponse|RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('google_id', $googleUser->id)->first();

        if ($user) {
            // Sign in an existing user (update email and avatar only)
            $user->update([
                'email' => $googleUser->email,
                'avatar' => $googleUser->avatar,
            ]);

            Auth::login($user);
            session()->regenerate();
            session()->save();

            return redirect(env('FRONTEND_CALLBACK_URL', ''));
        }

        // User doesn't exist. Store Google info in session and prompt for username to complete registration.
        session([
            'pending_user' => [
                'google_id' => $googleUser->id,
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'avatar' => $googleUser->avatar,
            ],
        ]);

        return redirect(env('FRONTEND_REGISTER_URL', ''));
    }

    public function getPendingUser()
    {
        $data = session('pending_user');
        if (! $data) {
            return response()->json(['error' => 'No pending registration'], 404);
        }

        return response()->json($data);
    }

    /**
     * Complete the sign up process with a manual username.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['string', 'max:255'],
            'username' => ['required', 'string', 'max:30', 'unique:users,username', 'regex:/^[a-z0-9_\.]+$/'],
        ]);

        $pendingUser = session('pending_user');

        if (! $pendingUser) {
            return response()->json(['message' => __('No pending registration found for this Google account.')], 400);
        }

        $user = User::create([
            'google_id' => $pendingUser['google_id'],
            'email' => $pendingUser['email'],
            'avatar' => $pendingUser['avatar'] ?? null,
            'name' => $validated['name'], // User modified name from form input
            'username' => $validated['username'],
        ]);

        session()->forget('pending_user');
        Auth::login($user);
        session()->regenerate();
        session()->save();

        return response()->json([
            'message' => __('Successfully registered and signed in.'),
        ], 201);
    }
}
