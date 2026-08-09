<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingStartingSoon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('bookings:send-starting-soon')]
#[Description('Notify customers whose confirmed booking is about to start (within the lead window)')]
class SendBookingStartingSoon extends Command
{
    /**
     * How many minutes before the start time the "starting soon" alert fires.
     */
    private const LEAD_MINUTES = 10;

    public function handle(): int
    {
        $now = Carbon::now();

        $bookings = Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereNull('starting_soon_sent_at')
            ->whereDate('booking_date', $now->toDateString())
            ->with(['user', 'court'])
            ->get()
            ->filter(function (Booking $booking) use ($now): bool {
                $start = Carbon::parse(
                    $booking->booking_date->format('Y-m-d').' '.$booking->start_time
                );

                // Fire once the start is within the lead window and not yet passed.
                return $now->lte($start)
                    && $now->gte($start->copy()->subMinutes(self::LEAD_MINUTES));
            });

        foreach ($bookings as $booking) {
            $booking->user->notify(new BookingStartingSoon($booking));
            // Mark immediately so a re-run never double-sends.
            $booking->update(['starting_soon_sent_at' => Carbon::now()]);
        }

        $this->info("Sent {$bookings->count()} starting-soon alert(s).");

        return self::SUCCESS;
    }
}
