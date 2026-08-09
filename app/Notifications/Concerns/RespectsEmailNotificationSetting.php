<?php

namespace App\Notifications\Concerns;

/**
 * Shared channel resolution for notifications that always deliver in-app but
 * only send email when the ENABLE_EMAIL_NOTIFICATION setting is turned on.
 */
trait RespectsEmailNotificationSetting
{
    /**
     * The delivery channels: the database channel is always included, and the
     * mail channel is added only when email notifications are enabled globally
     * AND the recipient actually has an email address (walk-in guests may not).
     *
     * @return array<int, string>
     */
    protected function notificationChannels(object $notifiable): array
    {
        $channels = ['database'];

        $emailEnabled = config('mail.notifications.email_enabled', true);
        $hasAddress = filled($notifiable->email ?? null);

        if ($emailEnabled && $hasAddress) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
