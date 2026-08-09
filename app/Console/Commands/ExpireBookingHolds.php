<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('bookings:expire-holds')]
#[Description('Mark pending-payment holds whose expiry has passed as expired, releasing their slots')]
class ExpireBookingHolds extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = Booking::query()
            ->where('status', BookingStatus::PendingPayment)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->update(['status' => BookingStatus::Expired]);

        $this->info("Expired {$count} booking hold(s).");

        return self::SUCCESS;
    }
}
