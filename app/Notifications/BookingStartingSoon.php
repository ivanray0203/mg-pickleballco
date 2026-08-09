<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Concerns\RespectsEmailNotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingStartingSoon extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsEmailNotificationSetting;

    public function __construct(public Booking $booking) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->notificationChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $b = $this->booking;

        return (new MailMessage)
            ->subject("Starting soon — your court at {$this->startTime()}")
            ->greeting('Your booking is starting soon!')
            ->line("Your court time is almost here — please get ready and head over so you're set to play.")
            ->line("Court: {$b->court->name}")
            ->line('Date: '.$b->booking_date->format('F j, Y'))
            ->line('Time: '.$this->timeRange())
            ->line("Booking Reference: {$b->booking_reference}")
            ->line('**Tip:** aim to arrive about 10 minutes early to warm up before your session begins.')
            ->action('View Booking', route('bookings.show', $b))
            ->line('Enjoy your game!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_starting_soon',
            'title' => 'Booking starting soon',
            'message' => "Your booking at {$this->booking->court->name} starts at {$this->startTime()}. Please get ready!",
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
        ];
    }

    private function startTime(): string
    {
        return substr($this->booking->start_time, 0, 5);
    }

    private function timeRange(): string
    {
        return substr($this->booking->start_time, 0, 5).' – '.substr($this->booking->end_time, 0, 5);
    }
}
