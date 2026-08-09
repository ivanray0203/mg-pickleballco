<?php

namespace App\Console\Commands;

use App\Enums\RegistrationStatus;
use App\Models\OpenPlayRegistration;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('open-play:expire-holds')]
#[Description('Cancel reserved open-play registrations whose payment hold has lapsed, releasing their slots')]
class ExpireOpenPlayHolds extends Command
{
    public function handle(): int
    {
        $count = OpenPlayRegistration::query()
            ->where('status', RegistrationStatus::Reserved)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->update([
                'status' => RegistrationStatus::Cancelled,
                'cancelled_at' => Carbon::now(),
            ]);

        $this->info("Expired {$count} open-play hold(s).");

        return self::SUCCESS;
    }
}
