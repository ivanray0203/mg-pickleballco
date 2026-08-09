<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('bookings:send-reminders')]
#[Description('Send a one-time reminder for confirmed bookings happening within the next day')]
class SendBookingReminders extends Command
{
    public function handle(): int
    {
        $bookings = Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereNull('reminder_sent_at')
            ->whereDate('booking_date', '>=', Carbon::today())
            ->whereDate('booking_date', '<=', Carbon::today()->addDay())
            ->with(['user', 'court'])
            ->get();

        foreach ($bookings as $booking) {
            $booking->user->notify(new BookingReminder($booking));
            // Mark immediately so a re-run never double-sends.
            $booking->update(['reminder_sent_at' => Carbon::now()]);
        }

        $this->info("Sent {$bookings->count()} booking reminder(s).");

        return self::SUCCESS;
    }
}
