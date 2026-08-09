<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Concerns\RespectsEmailNotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification implements ShouldQueue
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
            ->subject("Booking Approved — {$b->booking_reference}")
            ->greeting('Your booking is approved!')
            ->line('Great news — your court booking has been approved and confirmed. Here are the details:')
            ->line("Court: {$b->court->name}")
            ->line('Date: '.$b->booking_date->format('F j, Y'))
            ->line('Time: '.$this->timeRange())
            ->line("Duration: {$b->duration_hours} ".($b->duration_hours === 1 ? 'hour' : 'hours'))
            ->line('Amount: '.$this->money())
            ->line('Payment: GCash')
            ->line("Booking Reference: {$b->booking_reference}")
            ->line('**Please be ready about 10 minutes before your scheduled start time** so you can warm up and make the most of your session.')
            ->action('View Booking', route('bookings.show', $b))
            ->line('See you on the court!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_confirmed',
            'title' => 'Booking approved',
            'message' => "{$this->booking->court->name} on ".$this->booking->booking_date->format('M j, Y').' is approved. Please be ready ~10 minutes before your start time.',
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
        ];
    }

    private function timeRange(): string
    {
        return substr($this->booking->start_time, 0, 5).' – '.substr($this->booking->end_time, 0, 5);
    }

    private function money(): string
    {
        return '₱'.number_format($this->booking->total_amount / 100, 2);
    }
}
