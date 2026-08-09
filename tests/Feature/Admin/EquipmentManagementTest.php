<?php

use App\Enums\EquipmentType;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
});

/**
 * @return array<string, mixed>
 */
function validEquipmentPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Pro Graphite Paddle',
        'description' => 'A premium graphite paddle.',
        'type' => EquipmentType::Paddle->value,
        'hourly_rate' => 20.50,
        'total_quantity' => 10,
        'is_active' => true,
    ], $overrides);
}

test('admin can view the equipment list', function () {
    Equipment::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.equipment.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/equipment/index')
            ->has('equipment.data', 3)
            ->has('equipment.meta.links')
            ->has('equipment.meta.total')
            ->has('equipmentTypes', 4)
        );
});

test('the equipment list can be searched and filtered', function () {
    Equipment::factory()->paddle()->create(['name' => 'Alpha Paddle']);
    Equipment::factory()->ball()->create(['name' => 'Beta Balls']);
    Equipment::factory()->ball()->inactive()->create(['name' => 'Gamma Balls']);

    $this->actingAs($this->admin)
        ->get(route('admin.equipment.index', ['search' => 'Alpha']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('equipment.data', 1));

    $this->actingAs($this->admin)
        ->get(route('admin.equipment.index', ['type' => 'ball']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('equipment.data', 2));

    $this->actingAs($this->admin)
        ->get(route('admin.equipment.index', ['status' => 'inactive']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('equipment.data', 1));
});

test('customers cannot access equipment management', function () {
    $equipment = Equipment::factory()->create();

    $this->actingAs($this->customer)->get(route('admin.equipment.index'))->assertForbidden();
    $this->actingAs($this->customer)->get(route('admin.equipment.create'))->assertForbidden();
    $this->actingAs($this->customer)->post(route('admin.equipment.store'), validEquipmentPayload())->assertForbidden();
    $this->actingAs($this->customer)->get(route('admin.equipment.edit', $equipment))->assertForbidden();
    $this->actingAs($this->customer)->patch(route('admin.equipment.update', $equipment), validEquipmentPayload())->assertForbidden();
    $this->actingAs($this->customer)->patch(route('admin.equipment.toggle-status', $equipment))->assertForbidden();
});

test('guests are redirected to login', function () {
    $this->get(route('admin.equipment.index'))->assertRedirect(route('login'));
});

test('admin can create equipment and the rate is stored as cents', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('admin.equipment.store'), validEquipmentPayload([
            'image' => UploadedFile::fake()->image('paddle.jpg'),
        ]))
        ->assertRedirect(route('admin.equipment.index'));

    $equipment = Equipment::firstWhere('name', 'Pro Graphite Paddle');

    expect($equipment)->not->toBeNull()
        ->and($equipment->hourly_rate)->toBe(2050)
        ->and($equipment->total_quantity)->toBe(10)
        ->and($equipment->type)->toBe(EquipmentType::Paddle)
        ->and($equipment->slug)->toBe('pro-graphite-paddle')
        ->and($equipment->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($equipment->image_path);
});

test('creating equipment requires valid data', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.equipment.store'), validEquipmentPayload([
            'name' => '',
            'hourly_rate' => 0,
            'total_quantity' => -1,
        ]))
        ->assertSessionHasErrors(['name', 'hourly_rate', 'total_quantity']);
});

test('an invalid equipment type is rejected', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.equipment.store'), validEquipmentPayload(['type' => 'racket']))
        ->assertSessionHasErrors('type');
});

test('admin can update equipment', function () {
    $equipment = Equipment::factory()->paddle()->create(['name' => 'Old Name']);

    $this->actingAs($this->admin)
        ->patch(route('admin.equipment.update', $equipment), validEquipmentPayload([
            'name' => 'New Name',
            'type' => EquipmentType::Ball->value,
            'hourly_rate' => 40,
            'total_quantity' => 25,
        ]))
        ->assertRedirect(route('admin.equipment.index'));

    $equipment->refresh();

    expect($equipment->name)->toBe('New Name')
        ->and($equipment->slug)->toBe('new-name')
        ->and($equipment->type)->toBe(EquipmentType::Ball)
        ->and($equipment->hourly_rate)->toBe(4000)
        ->and($equipment->total_quantity)->toBe(25);
});

test('admin can activate and deactivate equipment', function () {
    $equipment = Equipment::factory()->create(['is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.equipment.toggle-status', $equipment))
        ->assertRedirect();

    expect($equipment->refresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)
        ->patch(route('admin.equipment.toggle-status', $equipment));

    expect($equipment->refresh()->is_active)->toBeTrue();
});
