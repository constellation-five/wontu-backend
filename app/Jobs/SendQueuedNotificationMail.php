<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Sends just the "mail" channel of a notification, on the queue — dispatched
 * by the NotificationSending listener (see AppServiceProvider) in place of
 * Laravel's normal synchronous mail send, so a slow/rate-limited mail
 * provider (Resend's free tier caps at 2 emails/sec) never blocks the
 * request thread or the broadcast/database channels, which still send
 * immediately as before.
 */
class SendQueuedNotificationMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly mixed $notifiable,
        public readonly Notification $notification,
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('resend-mail')];
    }

    public function handle(): void
    {
        // Bypass via() (which still lists 'broadcast'/'database' too) and
        // send only the mail channel — those other channels were already
        // sent synchronously before this job was queued.
        NotificationFacade::sendNow($this->notifiable, $this->notification, ['mail']);
    }
}
