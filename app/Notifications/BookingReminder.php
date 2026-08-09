<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Concerns\RespectsEmailNotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReminder extends Notification implements ShouldQueue
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
            ->subject("Reminder: your booking on {$b->booking_date->format('M j')}")
            ->greeting('Your court booking is coming up')
            ->line("Court: {$b->court->name}")
            ->line('Date: '.$b->booking_date->format('F j, Y'))
            ->line('Time: '.substr($b->start_time, 0, 5).' – '.substr($b->end_time, 0, 5))
            ->line("Booking Reference: {$b->booking_reference}")
            ->action('View Booking', route('bookings.show', $b));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_reminder',
            'title' => 'Upcoming booking',
            'message' => "Reminder: {$this->booking->court->name} on ".$this->booking->booking_date->format('M j, Y').' at '.substr($this->booking->start_time, 0, 5).'.',
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
        ];
    }
}
