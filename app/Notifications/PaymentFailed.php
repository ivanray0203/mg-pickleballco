<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Concerns\RespectsEmailNotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsEmailNotificationSetting;

    /**
     * @param  'failed'|'expired'  $reason
     */
    public function __construct(public Booking $booking, public string $reason = 'failed') {}

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
            ->subject("Payment {$this->reason} — {$b->booking_reference}")
            ->greeting('Your payment did not go through')
            ->line($this->summary())
            ->line("Booking Reference: {$b->booking_reference}")
            ->action('Try Booking Again', route('courts.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_'.$this->reason,
            'title' => $this->reason === 'expired' ? 'Payment expired' : 'Payment failed',
            'message' => $this->summary(),
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
        ];
    }

    private function summary(): string
    {
        return $this->reason === 'expired'
            ? "Your reservation hold for {$this->booking->court->name} expired before payment completed."
            : "Your payment for {$this->booking->court->name} could not be completed.";
    }
}
