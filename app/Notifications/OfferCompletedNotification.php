<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferCompletedNotification extends Notification implements ShouldBroadcastNow
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
            ->subject(__('Offer Complete! - Wontu'))
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        return [
            'title' => __('Offer Complete!'),
            'description' => __('The :merchant_name offer is now complete. Please proceed with payment.', ['merchant_name' => $this->offer->merchant_name]),
            'icon' => 'check_circle',
            'notification_type' => 'success',
            'action_url' => "/offers/{$this->offer->offer_id}",
        ];
    }
}
