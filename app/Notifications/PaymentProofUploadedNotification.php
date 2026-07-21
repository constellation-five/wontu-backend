<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;

class PaymentProofUploadedNotification extends Notification implements ShouldBroadcastNow
{
    use \App\Notifications\Traits\SendsWebPush, Queueable;

    public function __construct(
        public readonly User $buyer,
        public readonly Offer $offer,
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
            ->subject(__('Payment Proof Uploaded - Wontu'))
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        return [
            'template_key' => 'NOTIF_PAYMENT_PROOF_UPLOADED',
            'params' => ['buyer_name' => $this->buyer->name, 'merchant_name' => $this->offer->merchant_name],
            'icon' => 'payments',
            'notification_type' => 'info',
            'action_url' => "/offers/{$this->offer->offer_id}",
        ];
    }
}
