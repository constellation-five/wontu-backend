<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;

class OfferCreatedFromLikedRequestNotification extends Notification implements ShouldBroadcastNow
{
    use \App\Notifications\Traits\SendsWebPush, Queueable;

    public function __construct(
        public readonly Offer $offer,
        public readonly Request $request
    ) {}

    public function via(object $notifiable): array
    {
        return ['broadcast', 'database', 'mail', WebPushChannel::class];
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
            ->subject(__('New Offer from a Liked Request - Wontu'))
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        return [
            'template_key' => 'NOTIF_OFFER_CREATED_FROM_LIKED_REQUEST',
            'params' => [
                'merchant_name' => $this->offer->merchant_name,
                'request_title' => $this->request->item_name,
            ],
            'icon' => 'inventory_2',
            'notification_type' => 'info',
            'action_url' => "/offers/{$this->offer->offer_id}",
        ];
    }
}
