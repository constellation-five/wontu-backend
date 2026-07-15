<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ProfileController;
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

Route::get('/offers', [OfferController::class, 'index']);
Route::middleware('auth')->get('/offers/mine', [OfferController::class, 'myOffers']);
Route::get('/offers/{offer}', [OfferController::class, 'show']);
Route::get('/offers/{offer}/payment-methods', [OfferController::class, 'getPaymentMethods']);

Route::middleware('auth')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    // Offers Routes
    Route::post('/offers', [OfferController::class, 'store']);
    Route::post('/offers/{offer}/join', [OfferController::class, 'join']);
    Route::post('/offers/{offer}/complete', [OfferController::class, 'complete']);
    Route::post('/offers/{offer}/place-order', [OfferController::class, 'placeOrder']);
    Route::post('/offers/{offer}/update-order', [OfferController::class, 'updateOrder']);
    Route::post('/offers/{offer}/replace-order', [OfferController::class, 'replaceOrder']);
    Route::post('/offers/{offer}/cancel-order', [OfferController::class, 'cancelOrder']);
    Route::post('/offers/{offer}/close', [OfferController::class, 'close']);
    Route::post('/offers/{offer}/mark-arrived', [OfferController::class, 'markArrived']);
    Route::post('/offers/{offer}/submit-payment', [OfferController::class, 'submitPayment']);
    Route::get('/offers/{offer}/my-order', [OfferController::class, 'myOrder']);
    Route::get('/my-orders', [OfferController::class, 'myOrders']);
    Route::get('/offers/{offer}/orders', [OfferController::class, 'orders']);
    Route::post('/offers/{offer}/orders/respond-to-changes', [OfferController::class, 'respondToChanges']);
    Route::post('/offers/{offer}/orders/{offerBuyer}/confirm-payment', [OfferController::class, 'confirmPayment']);
    Route::put('/offers/{offer}', [OfferController::class, 'update']);
    Route::delete('/offers/{offer}', [OfferController::class, 'destroy']);
    Route::post('/uploads/image', [OfferController::class, 'uploadImage']);

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
    Route::get('/test-detail/{offer}', [OfferController::class, 'show']);

    // Request Routes
    Route::post('/requests', [RequestController::class, 'store']);
    Route::put('/requests/{id}', [RequestController::class, 'update']);
    Route::delete('/requests/{id}', [RequestController::class, 'destroy']);
    Route::post('/requests/{id}/vote', [RequestController::class, 'toggleVote']);

    // Notification Routes
    Route::prefix('notifications')->group(function (): void {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead']);
        Route::patch('/{id}/mark-read', [NotificationController::class, 'markRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/', [NotificationController::class, 'destroyAll']);
    });

    // Profile Routes
    Route::put('/profile', [ProfileController::class, 'updateProfile']);
    Route::get('/profile/personal-info', [ProfileController::class, 'personalInfo']);
    Route::get('/profile/{userId}', [ProfileController::class, 'show']);
    Route::post('/profile/{userId}/follow', [ProfileController::class, 'follow']);
    Route::delete('/profile/{userId}/unfollow', [ProfileController::class, 'unfollow']);
    Route::get('/profile/{userId}/followers', [ProfileController::class, 'followers']);
    Route::get('/profile/{userId}/following', [ProfileController::class, 'following']);
    Route::get('/profile/{userId}/rating-breakdown', [ProfileController::class, 'ratingBreakdown']);
    Route::post('/profile/{userId}/rating', [ProfileController::class, 'addRating']);
});
