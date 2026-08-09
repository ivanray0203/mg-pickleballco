<?php

namespace Database\Seeders;

use App\Enums\CourtType;
use App\Models\Court;
use App\Services\CourtScheduleService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourtSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a handful of sample courts.
     */
    public function run(CourtScheduleService $schedules): void
    {
        $courts = [
            ['name' => 'Center Court', 'type' => CourtType::Indoor, 'hourly_rate' => 4500, 'is_active' => true],
            ['name' => 'Garden Court', 'type' => CourtType::Outdoor, 'hourly_rate' => 3000, 'is_active' => true],
            ['name' => 'Riverside Court', 'type' => CourtType::Outdoor, 'hourly_rate' => 3500, 'is_active' => true],
            ['name' => 'Arena Court', 'type' => CourtType::Indoor, 'hourly_rate' => 5000, 'is_active' => false],
        ];

        foreach ($courts as $court) {
            $model = Court::firstOrCreate(
                ['slug' => Str::slug($court['name'])],
                [
                    'name' => $court['name'],
                    'description' => 'A well-maintained pickleball court.',
                    'type' => $court['type'],
                    'hourly_rate' => $court['hourly_rate'],
                    'opening_time' => '08:00',
                    'closing_time' => '22:00',
                    'is_active' => $court['is_active'],
                ],
            );

            $schedules->getOrCreateWeek($model);
        }
    }
}
