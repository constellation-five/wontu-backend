<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRatingNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public readonly User $rater,
        public readonly int $ratingValue,
        public readonly Offer $offer,
    ) {}

    public function via(object $notifiable): array
    {
        return ['broadcast', 'database', 'mail'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->data($notifiable));
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->data($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Rating Received - Wontu')
            ->view('emails.notification', ['data' => $this->data($notifiable)]);
    }

    private function data(object $notifiable): array
    {
        return [
            'type' => 'new_rating',
            'title' => 'New Rating Received',
            'description' => "{$this->rater->username} gave you {$this->ratingValue} stars for {$this->offer->category}.",
            'icon' => 'star',
            'action_url' => "/profile/{$notifiable->user_id}"
        ];
    }
}
