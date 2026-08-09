<?php

namespace App\Services;

use App\Enums\DayOfWeek;
use App\Models\Court;
use App\Models\CourtSchedule;
use Illuminate\Support\Collection;

class CourtScheduleService
{
    /**
     * Return the court's full weekly schedule, creating sensible defaults
     * (open every day using the court's base hours) for any missing days.
     *
     * @return Collection<int, CourtSchedule>
     */
    public function getOrCreateWeek(Court $court): Collection
    {
        $existing = $court->schedules()->get()->keyBy(fn (CourtSchedule $s) => $s->day_of_week->value);

        return collect(DayOfWeek::ordered())->map(function (DayOfWeek $day) use ($court, $existing) {
            return $existing->get($day->value) ?? $court->schedules()->create([
                'day_of_week' => $day,
                'opening_time' => $this->normalizeTime($court->opening_time),
                'closing_time' => $this->normalizeTime($court->closing_time),
                'is_open' => true,
            ]);
        })->values();
    }

    /**
     * Persist the full week of schedule rows from validated data.
     *
     * @param  array<int, array{day_of_week: int, is_open: bool, opening_time: ?string, closing_time: ?string}>  $days
     */
    public function sync(Court $court, array $days): void
    {
        foreach ($days as $day) {
            $isOpen = (bool) $day['is_open'];

            $court->schedules()->updateOrCreate(
                ['day_of_week' => $day['day_of_week']],
                [
                    'is_open' => $isOpen,
                    'opening_time' => $isOpen ? $day['opening_time'] : null,
                    'closing_time' => $isOpen ? $day['closing_time'] : null,
                ],
            );
        }
    }

    private function normalizeTime(?string $time): ?string
    {
        return $time === null ? null : substr($time, 0, 5);
    }
}
