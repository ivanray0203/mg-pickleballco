<?php

namespace Database\Seeders;

use App\Enums\OpenPlayGameSubmissionStatus;
use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SkillLevel;
use App\Models\Court;
use App\Models\OpenPlayGame;
use App\Models\OpenPlayGameSubmission;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\Season;
use App\Models\User;
use App\Services\OpenPlayMatchupService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OpenPlaySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a full spread of open-play scenarios covering every state: an open
     * free session, a paid session (with an unpaid hold), a full session, a
     * live in-progress session with games + a pending self-report, a completed
     * session with final standings, and a cancelled session — plus an active
     * season so the leaderboard has data.
     */
    public function run(): void
    {
        $courts = Court::query()->active()->orderBy('id')->get();

        if ($courts->isEmpty()) {
            return;
        }

        $court = $courts[0];
        $court2 = $courts->get(1) ?? $court;

        $players = $this->demoPlayers();

        $tomorrow = Carbon::tomorrow()->toDateString();
        $today = Carbon::today()->toDateString();
        $lastWeek = Carbon::today()->subDays(5)->toDateString();

        // An active season spanning the current year so the leaderboard shows
        // results from the live and completed sessions below.
        Season::firstOrCreate(
            ['name' => Carbon::now()->format('Y').' Season'],
            [
                'starts_on' => Carbon::now()->startOfYear()->toDateString(),
                'ends_on' => Carbon::now()->endOfYear()->toDateString(),
                'is_active' => true,
            ],
        );

        // 1. Free, open, upcoming — join / cancel flow, with spare capacity.
        $free = OpenPlaySession::factory()->for($court)->free()->create([
            'title' => 'Weeknight Free Play',
            'status' => OpenPlaySessionStatus::Open,
            'session_date' => $tomorrow,
            'start_time' => '18:00',
            'end_time' => '20:00',
            'capacity' => 8,
            'skill_level' => null,
            'notes' => 'Drop in, no fee. All levels welcome.',
        ]);
        $players->take(3)->each(fn (User $u) => $this->register($free, $u, RegistrationStatus::Confirmed));

        // 2. Paid, open, upcoming — GCash checkout flow, incl. one unpaid hold.
        $paid = OpenPlaySession::factory()->for($court2)->paid(15000)->create([
            'title' => 'Saturday Doubles (Paid)',
            'status' => OpenPlaySessionStatus::Open,
            'session_date' => $tomorrow,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'capacity' => 8,
            'skill_level' => SkillLevel::Novice,
            'notes' => '₱150 per player. Confirmed on payment.',
        ]);
        $players->slice(0, 4)->each(fn (User $u) => $this->register($paid, $u, RegistrationStatus::Confirmed));
        $this->register($paid, $players[4], RegistrationStatus::Reserved, Carbon::now()->addMinutes(10));

        // 3. Full — capacity reached, shows the "session full" state.
        $full = OpenPlaySession::factory()->for($court)->free()->create([
            'title' => 'Sunday Morning (Full)',
            'status' => OpenPlaySessionStatus::Full,
            'session_date' => $tomorrow,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'capacity' => 4,
            'skill_level' => SkillLevel::Expert,
        ]);
        $players->take(4)->each(fn (User $u) => $this->register($full, $u, RegistrationStatus::Confirmed));

        // 4. In progress today — checked-in roster, recorded games, live
        //    standings, and a pending player-reported result to review.
        $live = OpenPlaySession::factory()->for($court)->free()->create([
            'title' => 'Open Play — Live Now',
            'status' => OpenPlaySessionStatus::InProgress,
            'session_date' => $today,
            'start_time' => '17:00',
            'end_time' => '21:00',
            'capacity' => 28,
            'court_count' => 4,
            'skill_level' => null,
        ]);
        // Check in 24 players across 4 courts: a shuffle makes six 2v2 matches —
        // four playing at once (one per court) and two waiting in the queue.
        $liveRegs = $players->take(24)
            ->map(fn (User $u) => $this->register($live, $u, RegistrationStatus::CheckedIn))
            ->values();

        $this->recordGame($live, [$liveRegs[0], $liveRegs[1]], 11, [$liveRegs[2], $liveRegs[3]], 6);
        $this->recordGame($live, [$liveRegs[0], $liveRegs[2]], 11, [$liveRegs[1], $liveRegs[4]], 9);
        $this->recordGame($live, [$liveRegs[4], $liveRegs[5]], 8, [$liveRegs[0], $liveRegs[3]], 11);

        // Seed a shuffled match queue (one playing, one waiting) to try
        // auto-pairing right away.
        app(OpenPlayMatchupService::class)->generate($live);

        // A pending self-reported result awaiting organizer confirmation.
        OpenPlayGameSubmission::create([
            'open_play_session_id' => $live->id,
            'reported_by_user_id' => $liveRegs[1]->user_id,
            'team_a' => [$liveRegs[1]->id, $liveRegs[5]->id],
            'team_b' => [$liveRegs[2]->id, $liveRegs[3]->id],
            'team_a_score' => 11,
            'team_b_score' => 4,
            'status' => OpenPlayGameSubmissionStatus::Pending,
        ]);

        // 5. Completed last week — final podium + standings.
        $done = OpenPlaySession::factory()->for($court2)->free()->create([
            'title' => 'Last Week Shootout',
            'status' => OpenPlaySessionStatus::Completed,
            'session_date' => $lastWeek,
            'start_time' => '18:00',
            'end_time' => '20:00',
            'capacity' => 8,
            'skill_level' => SkillLevel::Novice,
        ]);
        $doneRegs = $players->slice(2, 4)
            ->map(fn (User $u) => $this->register($done, $u, RegistrationStatus::CheckedIn))
            ->values();
        $this->recordGame($done, [$doneRegs[0], $doneRegs[1]], 11, [$doneRegs[2], $doneRegs[3]], 7);
        $this->recordGame($done, [$doneRegs[0], $doneRegs[2]], 11, [$doneRegs[1], $doneRegs[3]], 5);
        $this->recordGame($done, [$doneRegs[0], $doneRegs[3]], 11, [$doneRegs[1], $doneRegs[2]], 9);

        // 6. Cancelled — shows the cancelled state and frees its court window.
        OpenPlaySession::factory()->for($court)->free()->create([
            'title' => 'Rained Out Session',
            'status' => OpenPlaySessionStatus::Cancelled,
            'session_date' => $tomorrow,
            'start_time' => '13:00',
            'end_time' => '15:00',
            'capacity' => 8,
        ]);
    }

    /**
     * Eight demo players with varied skill tiers (password: "password").
     *
     * @return Collection<int, User>
     */
    private function demoPlayers(): Collection
    {
        $roster = [
            ['Alice Reyes', SkillLevel::Expert],
            ['Ben Cruz', SkillLevel::Novice],
            ['Carla Uy', SkillLevel::Beginner],
            ['Diego Lim', SkillLevel::Expert],
            ['Ella Tan', SkillLevel::Novice],
            ['Frank Go', SkillLevel::Beginner],
            ['Grace Dizon', SkillLevel::Expert],
            ['Hugo Ramos', SkillLevel::Novice],
            ['Ivy Santos', SkillLevel::Beginner],
            ['Jake Flores', SkillLevel::Expert],
            ['Kara Mendoza', SkillLevel::Novice],
            ['Leo Aquino', SkillLevel::Beginner],
            ['Mia Villanueva', SkillLevel::Expert],
            ['Nico Bautista', SkillLevel::Novice],
            ['Olive Castro', SkillLevel::Beginner],
            ['Paolo Rivera', SkillLevel::Expert],
            ['Quinn Navarro', SkillLevel::Novice],
            ['Rita Domingo', SkillLevel::Beginner],
            ['Sam Ocampo', SkillLevel::Expert],
            ['Tina Gomez', SkillLevel::Novice],
            ['Uma Santos', SkillLevel::Beginner],
            ['Vince Torres', SkillLevel::Expert],
            ['Wendy Lao', SkillLevel::Novice],
            ['Xavier Cruz', SkillLevel::Beginner],
        ];

        return collect($roster)->map(fn (array $player, int $index): User => User::firstOrCreate(
            ['email' => 'player'.($index + 1).'@example.com'],
            [
                'name' => $player[0],
                'password' => bcrypt('password'),
                'email_verified_at' => Carbon::now(),
                'skill_level' => $player[1],
            ],
        ));
    }

    /**
     * Register a player for a session in a given state.
     */
    private function register(
        OpenPlaySession $session,
        User $user,
        RegistrationStatus $status,
        ?Carbon $expiresAt = null,
    ): OpenPlayRegistration {
        return OpenPlayRegistration::factory()
            ->for($session, 'session')
            ->for($user, 'user')
            ->create([
                'status' => $status,
                'checked_in_at' => $status === RegistrationStatus::CheckedIn ? Carbon::now() : null,
                'expires_at' => $expiresAt,
            ]);
    }

    /**
     * Persist a game with the given team registrations and score line.
     *
     * @param  array<int, OpenPlayRegistration>  $teamA
     * @param  array<int, OpenPlayRegistration>  $teamB
     */
    private function recordGame(OpenPlaySession $session, array $teamA, int $scoreA, array $teamB, int $scoreB): OpenPlayGame
    {
        $game = OpenPlayGame::factory()
            ->for($session, 'session')
            ->score($scoreA, $scoreB)
            ->create();

        foreach ($teamA as $registration) {
            $game->players()->create(['open_play_registration_id' => $registration->id, 'team' => 'a']);
        }

        foreach ($teamB as $registration) {
            $game->players()->create(['open_play_registration_id' => $registration->id, 'team' => 'b']);
        }

        return $game;
    }
}
