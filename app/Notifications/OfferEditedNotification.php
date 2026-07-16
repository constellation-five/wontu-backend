<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferEditedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public readonly Offer $offer,
        public readonly bool $disruptive,
        public readonly array $changes = [],
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
            ->subject('Offer Updated - Wontu')
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        return [
            'title' => $this->disruptive ? 'Important Offer Changes' : 'Offer Updated',
            'description' => $this->disruptive
                ? "The {$this->offer->merchant_name} offer you joined was changed in ways that may affect your order. Please review."
                : "The {$this->offer->merchant_name} offer you joined was updated by the seller.",
            'icon' => 'edit',
            'notification_type' => $this->disruptive ? 'warning' : 'info',
            'action_url' => "/offers/{$this->offer->offer_id}",
            'changes' => $this->changes,
        ];
    }
}
