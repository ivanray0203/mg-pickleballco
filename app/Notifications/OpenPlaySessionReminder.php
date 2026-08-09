<?php

namespace App\Notifications;

use App\Models\OpenPlaySession;
use App\Notifications\Concerns\RespectsEmailNotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OpenPlaySessionReminder extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsEmailNotificationSetting;

    public function __construct(public OpenPlaySession $session) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->notificationChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->session;

        return (new MailMessage)
            ->subject("Reminder: {$s->title} on {$s->session_date->format('M j')}")
            ->greeting('Your open play session is coming up')
            ->line("Session: {$s->title}")
            ->line("Court: {$s->court->name}")
            ->line('Date: '.$s->session_date->format('F j, Y'))
            ->line('Time: '.substr((string) $s->start_time, 0, 5).' – '.substr((string) $s->end_time, 0, 5))
            ->action('View Session', route('open-play.show', $s));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'open_play_reminder',
            'title' => 'Upcoming open play',
            'message' => "Reminder: {$this->session->title} on ".$this->session->session_date->format('M j, Y').' at '.substr((string) $this->session->start_time, 0, 5).'.',
            'session_id' => $this->session->id,
        ];
    }
}
