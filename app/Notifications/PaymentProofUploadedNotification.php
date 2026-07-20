<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentProofUploadedNotification extends Notification implements ShouldBroadcastNow
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
            ->subject(__('Payment Proof Uploaded - Wontu'))
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        return [
            'title' => __('Payment Proof Uploaded'),
            'description' => __(':buyer_name uploaded a payment proof for your :merchant_name offer.', ['buyer_name' => $this->buyer->name, 'merchant_name' => $this->offer->merchant_name]),
            'icon' => 'payments',
            'notification_type' => 'info',
            'action_url' => "/offers/{$this->offer->offer_id}",
        ];
    }
}
