<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewChatMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Message $message)
    {
    }

    public function via(object $notifiable): array
    {
        // Only send if the user has the 'chat-messages' setting enabled
        return $notifiable->getSetting('chat-messages', true) ? ['database', 'broadcast'] : [];
    }

    public function toArray(object $notifiable): array
    {
        $senderName = $this->message->sender?->name ?? 'System';
        
        $preview = $this->message->body;
        if (empty($preview) && $this->message->image_url) {
            $preview = 'Sent an image';
        }

        return [
            'title' => 'New message from ' . $senderName,
            'description' => str($preview)->limit(100),
            'icon' => 'chat',
            'action_url' => '/offers/' . $this->message->conversation->offer_id . '/chat',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $senderName = $this->message->sender?->name ?? 'System';
        $preview = $this->message->body ?: 'Sent an image';

        return new BroadcastMessage([
            'id' => $this->id,
            'title' => 'New message from ' . $senderName,
            'description' => str($preview)->limit(100),
            'icon' => 'chat',
            'action_url' => '/offers/' . $this->message->conversation->offer_id . '/chat',
            'created_at' => now()->toIso8601String(),
            'read_at' => null,
            'category' => 'chat-messages',
        ]);
    }
}
