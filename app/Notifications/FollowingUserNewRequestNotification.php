<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowingUserNewRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $poster,
        public readonly RequestModel $request,
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
            ->subject(__('notifications.NOTIF_FOLLOWING_NEW_REQUEST.title', $this->data()['params']))
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        return [
            'template_key' => 'NOTIF_FOLLOWING_NEW_REQUEST',
            'params' => ['user_name' => $this->poster->name, 'request_name' => $this->request->item_name],
            'icon' => 'inventory_2',
            'notification_type' => 'info',
            'action_url' => "/requests/{$this->request->request_id}",
        ];
    }
}
