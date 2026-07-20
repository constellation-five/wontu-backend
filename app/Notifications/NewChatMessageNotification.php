<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewChatMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Message $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
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
        $senderName = $this->message->sender?->name ?? 'System';

        return (new MailMessage)
            ->subject(__('New message from :sender - Wontu', ['sender' => $senderName]))
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        $senderName = $this->message->sender?->name ?? 'System';
        
        $preview = $this->message->body;
        if (empty($preview) && $this->message->image_url) {
            $preview = __('Sent an image');
        }

        return [
            'title' => __('New message from :sender', ['sender' => $senderName]),
            'description' => str($preview)->limit(100),
            'icon' => 'chat',
            'notification_type' => 'info',
            'action_url' => $this->message->conversation->type === 'offer_group' ? '/offers/' . $this->message->conversation->offer_id . '/chat' : '/chat/' . $this->message->conversation->id,
        ];
    }
}
