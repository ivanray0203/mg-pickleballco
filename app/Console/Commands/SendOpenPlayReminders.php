<?php

namespace App\Console\Commands;

use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Models\OpenPlaySession;
use App\Notifications\OpenPlaySessionReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('open-play:send-reminders')]
#[Description('Send a one-time reminder to registrants of open-play sessions happening within the next day')]
class SendOpenPlayReminders extends Command
{
    public function handle(): int
    {
        $sessions = OpenPlaySession::query()
            ->whereNull('reminder_sent_at')
            ->whereNotIn('status', [OpenPlaySessionStatus::Cancelled, OpenPlaySessionStatus::Completed])
            ->whereDate('session_date', '>=', Carbon::today())
            ->whereDate('session_date', '<=', Carbon::today()->addDay())
            ->with(['court', 'registrations' => fn ($query) => $query
                ->whereIn('status', [RegistrationStatus::Confirmed, RegistrationStatus::CheckedIn])
                ->with('user'),
            ])
            ->get();

        $notified = 0;

        foreach ($sessions as $session) {
            foreach ($session->registrations as $registration) {
                $registration->user->notify(new OpenPlaySessionReminder($session));
                $notified++;
            }

            // Mark immediately so a re-run never double-sends.
            $session->update(['reminder_sent_at' => Carbon::now()]);
        }

        $this->info("Sent {$notified} open play reminder(s) across {$sessions->count()} session(s).");

        return self::SUCCESS;
    }
}
