<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferDeletedNotification extends Notification implements ShouldBroadcastNow
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
            ->subject(__('Offer Deleted - Wontu'))
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        return [
            'title' => __('Offer Deleted'),
            'description' => __('The :merchant_name offer you joined was deleted by the seller. Your order has been cancelled.', ['merchant_name' => $this->offer->merchant_name]),
            'icon' => 'delete',
            'notification_type' => 'error',
            'action_url' => '/offers',
        ];
    }
}
