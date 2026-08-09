<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Concerns\RespectsEmailNotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelled extends Notification implements ShouldQueue
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
            ->subject("Booking Cancelled — {$b->booking_reference}")
            ->greeting('Your booking was cancelled')
            ->line("Court: {$b->court->name}")
            ->line('Date: '.$b->booking_date->format('F j, Y'))
            ->line("Booking Reference: {$b->booking_reference}")
            ->action('Book Another Court', route('courts.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_cancelled',
            'title' => 'Booking cancelled',
            'message' => "Your booking for {$this->booking->court->name} on ".$this->booking->booking_date->format('M j, Y').' was cancelled.',
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
        ];
    }
}
