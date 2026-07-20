<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BuyerRemovedFromOfferNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public readonly Offer $offer,
        public readonly array $removedItemNames = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['broadcast', 'database', 'mail'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->data());
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->data();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your Order Was Removed - Wontu'))
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        $items = implode(', ', $this->removedItemNames);

        return [
            'title' => __('Your Order Was Removed'),
            'description' => __('Your order in the :merchant_name offer was removed because the seller reduced the available stock (:items).', ['merchant_name' => $this->offer->merchant_name, 'items' => $items]),
            'icon' => 'remove_shopping_cart',
            'notification_type' => 'error',
            'action_url' => "/offers/{$this->offer->offer_id}",
        ];
    }
}
