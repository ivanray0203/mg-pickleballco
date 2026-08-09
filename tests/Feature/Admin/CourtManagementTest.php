<?php

use App\Enums\CourtType;
use App\Models\Court;
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
function validCourtPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Championship Court',
        'description' => 'A premium indoor court.',
        'type' => CourtType::Indoor->value,
        'hourly_rate' => 25.50,
        'opening_time' => '08:00',
        'closing_time' => '22:00',
        'is_active' => true,
    ], $overrides);
}

test('admin can view the court list', function () {
    Court::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.courts.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/courts/index')
            ->has('courts.data', 3)
            ->has('courts.meta.links')
            ->has('courts.meta.total')
            ->has('courtTypes', 2)
        );
});

test('the court list can be searched and filtered', function () {
    Court::factory()->indoor()->create(['name' => 'Alpha Arena']);
    Court::factory()->outdoor()->create(['name' => 'Beta Field']);
    Court::factory()->outdoor()->inactive()->create(['name' => 'Gamma Field']);

    $this->actingAs($this->admin)
        ->get(route('admin.courts.index', ['search' => 'Alpha']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('courts.data', 1));

    $this->actingAs($this->admin)
        ->get(route('admin.courts.index', ['type' => 'outdoor']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('courts.data', 2));

    $this->actingAs($this->admin)
        ->get(route('admin.courts.index', ['status' => 'inactive']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('courts.data', 1));
});

test('customers cannot access court management', function () {
    $court = Court::factory()->create();

    $this->actingAs($this->customer)->get(route('admin.courts.index'))->assertForbidden();
    $this->actingAs($this->customer)->get(route('admin.courts.create'))->assertForbidden();
    $this->actingAs($this->customer)->post(route('admin.courts.store'), validCourtPayload())->assertForbidden();
    $this->actingAs($this->customer)->get(route('admin.courts.edit', $court))->assertForbidden();
    $this->actingAs($this->customer)->patch(route('admin.courts.update', $court), validCourtPayload())->assertForbidden();
    $this->actingAs($this->customer)->patch(route('admin.courts.toggle-status', $court))->assertForbidden();
});

test('guests are redirected to login', function () {
    $this->get(route('admin.courts.index'))->assertRedirect(route('login'));
});

test('admin can create a court and the rate is stored as cents', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('admin.courts.store'), validCourtPayload([
            'image' => UploadedFile::fake()->image('court.jpg'),
        ]))
        ->assertRedirect(route('admin.courts.index'));

    $court = Court::firstWhere('name', 'Championship Court');

    expect($court)->not->toBeNull()
        ->and($court->hourly_rate)->toBe(2550)
        ->and($court->type)->toBe(CourtType::Indoor)
        ->and($court->slug)->toBe('championship-court')
        ->and($court->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($court->image_path);
});

test('creating a court requires valid data', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.courts.store'), validCourtPayload([
            'name' => '',
            'hourly_rate' => 0,
        ]))
        ->assertSessionHasErrors(['name', 'hourly_rate']);
});

test('an invalid court type is rejected', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.courts.store'), validCourtPayload(['type' => 'rooftop']))
        ->assertSessionHasErrors('type');
});

test('closing time must be after opening time', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.courts.store'), validCourtPayload([
            'opening_time' => '22:00',
            'closing_time' => '08:00',
        ]))
        ->assertSessionHasErrors('closing_time');
});

test('admin can update a court', function () {
    $court = Court::factory()->indoor()->create(['name' => 'Old Name']);

    $this->actingAs($this->admin)
        ->patch(route('admin.courts.update', $court), validCourtPayload([
            'name' => 'New Name',
            'type' => CourtType::Outdoor->value,
            'hourly_rate' => 40,
        ]))
        ->assertRedirect(route('admin.courts.index'));

    $court->refresh();

    expect($court->name)->toBe('New Name')
        ->and($court->slug)->toBe('new-name')
        ->and($court->type)->toBe(CourtType::Outdoor)
        ->and($court->hourly_rate)->toBe(4000);
});

test('replacing a court image deletes the previous file', function () {
    Storage::fake('public');

    $court = Court::factory()->create([
        'image_path' => UploadedFile::fake()->image('old.jpg')->store('courts', 'public'),
    ]);
    $oldPath = $court->image_path;

    $this->actingAs($this->admin)
        ->patch(route('admin.courts.update', $court), validCourtPayload([
            'image' => UploadedFile::fake()->image('new.jpg'),
        ]));

    $court->refresh();

    expect($court->image_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($court->image_path);
});

test('admin can activate and deactivate a court', function () {
    $court = Court::factory()->create(['is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.courts.toggle-status', $court))
        ->assertRedirect();

    expect($court->refresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)
        ->patch(route('admin.courts.toggle-status', $court));

    expect($court->refresh()->is_active)->toBeTrue();
});
