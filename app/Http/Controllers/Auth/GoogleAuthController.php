<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect(): JsonResponse|RedirectResponse
    {
        /** @var GoogleProvider $provider */
        $provider = Socialite::driver('google');

        return $provider->stateless()->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function callback(): JsonResponse|RedirectResponse
    {
        /** @var GoogleProvider $provider */
        $provider = Socialite::driver('google');

        $googleUser = $provider->stateless()->user();

        $user = User::where('google_id', $googleUser->id)->first();

        if ($user) {
            // Sign in an existing user (update email and avatar only)
            $user->update([
                'email' => $googleUser->email,
                'avatar' => $googleUser->avatar,
            ]);

            Auth::login($user);

            return response()->json([
                'message' => 'Successfully signed in with Google.',
                'user' => $user,
            ]);
        }

        // User doesn't exist. Ask frontend to complete sign up (provide username)
        return response()->json([
            'message' => 'User not found. Please complete registration by providing a username.',
            'action' => 'register',
            'google_user' => [
                'google_id' => $googleUser->id,
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'avatar' => $googleUser->avatar,
            ],
        ]);
    }

    /**
     * Complete the sign up process with a manual username.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'google_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'avatar' => ['nullable', 'string', 'url'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
        ]);

        if (User::where('google_id', $validated['google_id'])->exists()) {
            return response()->json(['message' => 'User already registered.'], 400);
        }

        $user = User::create([
            'google_id' => $validated['google_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'avatar' => $validated['avatar'] ?? null,
            'username' => $validated['username'],
        ]);

        Auth::login($user);

        return response()->json([
            'message' => 'Successfully registered and signed in.',
            'user' => $user,
        ], 201);
    }
}
