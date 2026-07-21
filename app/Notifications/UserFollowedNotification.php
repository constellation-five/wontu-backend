<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;

class UserFollowedNotification extends Notification implements ShouldBroadcastNow
{
    use \App\Notifications\Traits\SendsWebPush, Queueable;

    public function __construct(
        public readonly User $follower,
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
            ->subject(__('New Follower - Wontu'))
            ->view('emails.notification', ['data' => $this->data()]);
    }

    private function data(): array
    {
        return [
            'template_key' => 'NOTIF_USER_FOLLOWED',
            'params' => ['follower_name' => $this->follower->name],
            'icon' => 'person_add',
            'notification_type' => 'info',
            'action_url' => "/profile/{$this->follower->user_id}",
        ];
    }
}
