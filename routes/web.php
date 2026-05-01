<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentMethodController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function (): array {
    return [
        'status' => 'ok',
    ];
});

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
Route::get('/auth/google/pending-user', [GoogleAuthController::class, 'getPendingUser']);
Route::post('/auth/google/register', [GoogleAuthController::class, 'register']);

Route::middleware('auth')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/offers', [OfferController::class, 'store']);
    Route::post('/offers/{offer}/join', [OfferController::class, 'join']);
    Route::post('/offers/{offer}/orders', [OrderController::class, 'submitOrder']);
    Route::get('/offers', [OfferController::class, 'index']);

    // Order detail
    Route::get('/seller/offers/{offer}/buyers/{buyer}', [OrderController::class, 'showOrderDetails']);

    Route::post('/auth/logout', function (Request $request) {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Successfully logged out']);
    });

    // Payment Method Routes
    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
    Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
    Route::put('/payment-methods/{id}', [PaymentMethodController::class, 'update']);
    Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy']);
});
