<?php

namespace App\Providers;

use App\Jobs\SendQueuedNotificationMail;
use App\Models\User;
use App\Models\UserSetting;
use App\Support\NotificationCategories;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Resend's free tier caps at 2 emails/sec — every notification's
        // mail channel funnels through this one named limiter (see the
        // NotificationSending listener below), regardless of which
        // notification class triggered it.
        RateLimiter::for('resend-mail', function () {
            return Limit::perSecond(2);
        });

        // Intercept every notification's "mail" channel before Laravel sends
        // it synchronously: skip it entirely if the recipient has turned off
        // email for that notification's category (see NotificationCategories),
        // and otherwise hand it off to a rate-limited queued job instead, so
        // a slow/throttled mail provider never blocks the request thread.
        // The 'broadcast'/'database' channels are unaffected and still send
        // immediately, regardless of this setting.
        Event::listen(NotificationSending::class, function (NotificationSending $event) {
            if ($event->channel !== 'mail') {
                return true;
            }

            if (SendQueuedNotificationMail::$isSending) {
                return true;
            }

            if ($event->notifiable instanceof User && ! $this->wantsEmailFor($event->notifiable, $event->notification)) {
                return false;
            }

            SendQueuedNotificationMail::dispatch($event->notifiable, $event->notification);

            return false;
        });
    }

    private function wantsEmailFor(User $user, Notification $notification): bool
    {
        $category = NotificationCategories::categoryFor($notification);

        // Not a categorized notification (shouldn't happen for anything we
        // send today) — default to sending, same as a user who never
        // touched their settings.
        if ($category === null) {
            return true;
        }

        $settings = UserSetting::where('user_id', $user->user_id)->first();

        if (! $settings || empty($settings->notifications)) {
            return true;
        }

        return ($settings->notifications[$category]['email'] ?? true) !== false;
    }
}
