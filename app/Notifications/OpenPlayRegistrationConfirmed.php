<?php

namespace App\Notifications;

use App\Models\OpenPlayRegistration;
use App\Notifications\Concerns\RespectsEmailNotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OpenPlayRegistrationConfirmed extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsEmailNotificationSetting;

    public function __construct(public OpenPlayRegistration $registration) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->notificationChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $session = $this->registration->session;

        return (new MailMessage)
            ->subject("You're in — {$session->title}")
            ->greeting('Your open play spot is confirmed!')
            ->line("Session: {$session->title}")
            ->line("Court: {$session->court->name}")
            ->line('Date: '.$session->session_date->format('F j, Y'))
            ->line('Time: '.substr((string) $session->start_time, 0, 5).' – '.substr((string) $session->end_time, 0, 5))
            ->action('View Session', route('open-play.show', $session))
            ->line('See you on the court!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $session = $this->registration->session;

        return [
            'type' => 'open_play_registration_confirmed',
            'title' => 'Open play confirmed',
            'message' => "You're confirmed for {$session->title} on ".$session->session_date->format('M j, Y').'.',
            'session_id' => $session->id,
        ];
    }
}
