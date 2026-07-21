<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;

class OfferEditedNotification extends Notification implements ShouldBroadcastNow
{
    use \App\Notifications\Traits\SendsWebPush, Queueable;

    public function __construct(
        public readonly Offer $offer,
        public readonly bool $disruptive,
        public readonly array $changes = [],
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
            ->subject(__('Offer Updated - Wontu'))
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        return [
            'template_key' => $this->disruptive ? 'NOTIF_OFFER_EDITED_DISRUPTIVE' : 'NOTIF_OFFER_EDITED',
            'params' => ['merchant_name' => $this->offer->merchant_name],
            'icon' => 'edit',
            'notification_type' => $this->disruptive ? 'warning' : 'info',
            'action_url' => "/offers/{$this->offer->offer_id}",
            'changes' => $this->changes,
        ];
    }
}
