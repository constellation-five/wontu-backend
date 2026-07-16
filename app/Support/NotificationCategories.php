<?php

namespace App\Support;

use App\Notifications\BuyerJoinedNotification;
use App\Notifications\BuyerRemovedFromOfferNotification;
use App\Notifications\ItemAdjustedNotification;
use App\Notifications\ItemsArrivedNotification;
use App\Notifications\OfferAutoClosedSoldOutNotification;
use App\Notifications\OfferClosedNotification;
use App\Notifications\OfferClosingReachedNotSoldOutNotification;
use App\Notifications\OfferCompletedNotification;
use App\Notifications\OfferDeletedNotification;
use App\Notifications\OfferEditedNotification;
use App\Notifications\OfferSoldOutEarlyNotification;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderUpdatedNotification;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\PaymentProofUploadedNotification;
use App\Notifications\UserFollowedNotification;
use Illuminate\Notifications\Notification;

/**
 * Single source of truth mapping every notification class to the
 * user_settings.notifications category that controls it — kept here rather
 * than on each notification class so the settings UI (frontend) and the
 * send-gating logic (AppServiceProvider) both read from the same list.
 */
class NotificationCategories
{
    /**
     * @return array<string, array{label: string, description: string, notifications: array<class-string<Notification>>}>
     */
    public static function all(): array
    {
        return [
            'new-orders' => [
                'label' => 'New orders & payments',
                'description' => 'A buyer joins, places, updates, or cancels an order on one of your offers, or submits proof of payment.',
                'notifications' => [
                    BuyerJoinedNotification::class,
                    OrderPlacedNotification::class,
                    OrderUpdatedNotification::class,
                    OrderCancelledNotification::class,
                    PaymentProofUploadedNotification::class,
                ],
            ],
            'offer-lifecycle' => [
                'label' => 'Offer status changes',
                'description' => 'One of your offers closes automatically — either it reached its closing time or sold out.',
                'notifications' => [
                    OfferClosingReachedNotSoldOutNotification::class,
                    OfferAutoClosedSoldOutNotification::class,
                    OfferSoldOutEarlyNotification::class,
                ],
            ],
            'offer-updates' => [
                'label' => 'Offer updates',
                'description' => 'An offer you\'ve joined is edited, closed, deleted, or completed, or your order on it is adjusted or removed.',
                'notifications' => [
                    OfferEditedNotification::class,
                    OfferClosedNotification::class,
                    OfferDeletedNotification::class,
                    OfferCompletedNotification::class,
                    BuyerRemovedFromOfferNotification::class,
                    ItemAdjustedNotification::class,
                ],
            ],
            'order-status' => [
                'label' => 'Order status',
                'description' => 'Your payment is confirmed, or the items you ordered have arrived.',
                'notifications' => [
                    PaymentConfirmedNotification::class,
                    ItemsArrivedNotification::class,
                ],
            ],
            'social' => [
                'label' => 'Social',
                'description' => 'Someone follows your profile.',
                'notifications' => [
                    UserFollowedNotification::class,
                ],
            ],
        ];
    }

    public static function categoryFor(Notification $notification): ?string
    {
        $class = get_class($notification);

        foreach (self::all() as $category => $definition) {
            if (in_array($class, $definition['notifications'], true)) {
                return $category;
            }
        }

        return null;
    }
}
