<?php

use App\Enums\CourtBlockReason;
use App\Enums\DayOfWeek;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\CourtSchedule;
use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create();

    // Open every day 06:00–22:00 so blocks have a schedule to sit within.
    foreach (DayOfWeek::cases() as $day) {
        CourtSchedule::factory()->for($this->court)->create([
            'day_of_week' => $day,
            'opening_time' => '06:00',
            'closing_time' => '22:00',
            'is_open' => true,
        ]);
    }

    // A safely-future date for valid blocks.
    $this->futureDate = now()->addWeek()->toDateString();
});

/**
 * @return array<string, mixed>
 */
function blockPayload(array $overrides = []): array
{
    return array_merge([
        'block_date' => now()->addWeek()->toDateString(),
        'start_time' => '08:00',
        'end_time' => '12:00',
        'reason' => CourtBlockReason::Maintenance->value,
        'notes' => 'Resurfacing the court.',
    ], $overrides);
}

test('admin can create a court block', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.courts.blocks.store', $this->court), blockPayload())
        ->assertRedirect();

    $block = CourtBlock::first();

    expect($block)->not->toBeNull()
        ->and($block->court_id)->toBe($this->court->id)
        ->and($block->reason)->toBe(CourtBlockReason::Maintenance)
        ->and($block->created_by)->toBe($this->admin->id);
});

test('customers cannot create or delete blocks', function () {
    $block = CourtBlock::factory()->for($this->court)->create();

    $this->actingAs($this->customer)
        ->post(route('admin.courts.blocks.store', $this->court), blockPayload())
        ->assertForbidden();

    $this->actingAs($this->customer)
        ->delete(route('admin.courts.blocks.destroy', [$this->court, $block]))
        ->assertForbidden();
});

test('a block requires a reason', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.courts.blocks.store', $this->court), blockPayload(['reason' => '']))
        ->assertSessionHasErrors('reason');
});

test('an invalid reason is rejected', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.courts.blocks.store', $this->court), blockPayload(['reason' => 'nonsense']))
        ->assertSessionHasErrors('reason');
});

test('block times must be whole hours', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.courts.blocks.store', $this->court), blockPayload([
            'start_time' => '08:30',
        ]))
        ->assertSessionHasErrors('start_time');
});

test('block end time must be after start time', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.courts.blocks.store', $this->court), blockPayload([
            'start_time' => '12:00',
            'end_time' => '08:00',
        ]))
        ->assertSessionHasErrors('end_time');
});

test('a block cannot fall outside operating hours', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.courts.blocks.store', $this->court), blockPayload([
            'start_time' => '05:00',
            'end_time' => '06:00',
        ]))
        ->assertSessionHasErrors('start_time');
});

test('a block cannot be placed on a closed day', function () {
    $date = now()->addWeek();
    $this->court->schedules()
        ->where('day_of_week', $date->dayOfWeek)
        ->update(['is_open' => false, 'opening_time' => null, 'closing_time' => null]);

    $this->actingAs($this->admin)
        ->post(route('admin.courts.blocks.store', $this->court), blockPayload([
            'block_date' => $date->toDateString(),
        ]))
        ->assertSessionHasErrors('start_time');
});

test('a block cannot be in the past', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.courts.blocks.store', $this->court), blockPayload([
            'block_date' => now()->subDay()->toDateString(),
        ]))
        ->assertSessionHasErrors('block_date');
});

test('admin can delete a block', function () {
    $block = CourtBlock::factory()->for($this->court)->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.courts.blocks.destroy', [$this->court, $block]))
        ->assertRedirect();

    expect(CourtBlock::find($block->id))->toBeNull();
});

test('blocks are scoped to their court on delete', function () {
    $otherCourt = Court::factory()->create();
    $block = CourtBlock::factory()->for($otherCourt)->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.courts.blocks.destroy', [$this->court, $block]))
        ->assertNotFound();
});

test('a created block makes the matching slots unavailable', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.courts.blocks.store', $this->court), blockPayload([
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]));

    $this->actingAs($this->admin)
        ->get(route('admin.courts.availability.index', [
            'court' => $this->court,
            'date' => $this->futureDate,
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/courts/availability')
            ->where('availability.slots', function (Collection $slots) {
                $byStart = $slots->keyBy('start_time');

                return $byStart['08:00']['status'] === 'blocked'
                    && $byStart['09:00']['status'] === 'blocked'
                    && $byStart['07:00']['status'] === 'available';
            })
        );
});
