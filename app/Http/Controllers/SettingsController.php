<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * Get user settings (creates default if not exists)
     */
    public function index()
    {
        $user = Auth::user();

        $settings = UserSetting::firstOrCreate(
            ['user_id' => $user->user_id],
            [
                'notifications' => UserSetting::getDefaultNotifications(),
                'language' => 'en',
                'dark_mode' => false,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => __('Settings retrieved successfully'),
            'data' => [
                'notifications' => $settings->notifications,
                'language' => $settings->language,
                'dark_mode' => $settings->dark_mode,
            ],
        ], 200);
    }

    /**
     * Update user settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'notifications' => 'sometimes|array',
            'notifications.*.push' => 'sometimes|boolean',
            'notifications.*.email' => 'sometimes|boolean',
            'language' => ['sometimes', 'string', Rule::in(['en', 'id'])],
            'dark_mode' => 'sometimes|boolean',
        ]);

        $user = Auth::user();

        $settings = UserSetting::firstOrCreate(
            ['user_id' => $user->user_id],
            [
                'notifications' => UserSetting::getDefaultNotifications(),
                'language' => 'en',
                'dark_mode' => false,
            ]
        );

        // Update only provided fields
        if (isset($validated['notifications'])) {
            $settings->notifications = $validated['notifications'];
        }
        if (isset($validated['language'])) {
            $settings->language = $validated['language'];
        }
        if (isset($validated['dark_mode'])) {
            $settings->dark_mode = $validated['dark_mode'];
        }

        $settings->save();

        return response()->json([
            'success' => true,
            'message' => __('Settings updated successfully'),
            'data' => [
                'notifications' => $settings->notifications,
                'language' => $settings->language,
                'dark_mode' => $settings->dark_mode,
            ],
        ], 200);
    }
}
