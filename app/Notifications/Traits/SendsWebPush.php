<?php

namespace App\Notifications\Traits;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

trait SendsWebPush
{
    /**
     * Get the web push representation of the notification.
     *
     * @param  mixed  $notifiable
     * @param  Notification  $notification
     * @return WebPushMessage
     */
    public function toWebPush($notifiable, $notification)
    {
        // By the time this is called, Laravel has already switched the
        // App::getLocale() to the user's preferred locale.
        $data = $this->data();

        $title = isset($data['template_key'])
            ? __('notifications.'.$data['template_key'].'.title')
            : ($data['title'] ?? 'Wontu Notification');

        $body = isset($data['template_key'])
            ? __('notifications.'.$data['template_key'].'.description', $data['params'] ?? [])
            : ($data['description'] ?? '');

        return (new class extends WebPushMessage {
            public function toArray(): array
            {
                return ['notification' => parent::toArray()];
            }
        })
            ->title($title)
            ->body($body)
            ->icon('https://wontu.site/assets/img/wontulogo.svg')
            ->data(['url' => $data['action_url'] ?? '/']);
    }
}
