<?php

namespace App\Notifications;

use App\Models\OpenPlaySession;
use App\Notifications\Concerns\RespectsEmailNotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OpenPlayResultsPosted extends Notification implements ShouldQueue
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
            ->subject("Results are in — {$s->title}")
            ->greeting('The final standings are posted')
            ->line("Session: {$s->title}")
            ->line('Date: '.$s->session_date->format('F j, Y'))
            ->action('View Standings', route('open-play.show', $s))
            ->line('Thanks for playing!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'open_play_results',
            'title' => 'Open play results posted',
            'message' => "Final standings for {$this->session->title} are now available.",
            'session_id' => $this->session->id,
        ];
    }
}
