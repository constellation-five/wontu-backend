<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowingUserNewOfferNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $poster,
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
            ->subject(__('notifications.NOTIF_FOLLOWING_NEW_OFFER.title', $this->data()['params']))
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        return [
            'template_key' => 'NOTIF_FOLLOWING_NEW_OFFER',
            'params' => ['user_name' => $this->poster->name, 'offer_name' => $this->offer->merchant_name],
            'icon' => 'local_offer',
            'notification_type' => 'info',
            'action_url' => "/offers/{$this->offer->offer_id}",
        ];
    }
}
