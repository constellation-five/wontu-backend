<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferAutoClosedSoldOutNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
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
            ->subject(__('Offer Closed - Sold Out - Wontu'))
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        return [
            'title' => __('Offer Sold Out and Closed'),
            'description' => __('Your :merchant_name offer reached its closing time fully sold out and has been closed automatically.', ['merchant_name' => $this->offer->merchant_name]),
            'icon' => 'inventory_2',
            'notification_type' => 'success',
            'action_url' => "/offers/{$this->offer->offer_id}",
        ];
    }
}
