<?php

namespace App\Notifications;

use App\Models\OpenPlaySession;
use App\Notifications\Concerns\RespectsEmailNotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OpenPlaySessionCancelled extends Notification implements ShouldQueue
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
            ->subject("Open play cancelled — {$s->title}")
            ->greeting('An open play session was cancelled')
            ->line("Session: {$s->title}")
            ->line("Court: {$s->court->name}")
            ->line('Date: '.$s->session_date->format('F j, Y'))
            ->line('Any payment made for this session will be refunded per policy.')
            ->action('Browse Open Play', route('open-play.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'open_play_cancelled',
            'title' => 'Open play cancelled',
            'message' => "{$this->session->title} on ".$this->session->session_date->format('M j, Y').' was cancelled.',
            'session_id' => $this->session->id,
        ];
    }
}
