<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCancelledNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public readonly User $buyer,
        public readonly Offer $offer,
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
            ->subject(__('Order Cancelled - Wontu'))
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        return [
            'title' => __('Order Cancelled'),
            'description' => __(':buyer_name cancelled their order in your :merchant_name offer.', ['buyer_name' => $this->buyer->name, 'merchant_name' => $this->offer->merchant_name]),
            'icon' => 'cancel',
            'notification_type' => 'warning',
            'action_url' => "/offers/{$this->offer->offer_id}",
        ];
    }
}
