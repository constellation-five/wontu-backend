<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ItemAdjustedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public readonly Offer $offer,
        public readonly string $itemName,
        public readonly int $newQuantity,
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
            ->subject('Your Order Was Adjusted - Wontu')
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        return [
            'title' => 'Your Order Was Adjusted',
            'description' => "The seller reduced available stock for '{$this->itemName}' in the {$this->offer->merchant_name} offer, so your quantity was adjusted to {$this->newQuantity}.",
            'icon' => 'edit_note',
            'notification_type' => 'warning',
            'action_url' => "/offers/{$this->offer->offer_id}",
        ];
    }
}
