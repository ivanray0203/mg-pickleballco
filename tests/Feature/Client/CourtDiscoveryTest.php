<?php

use App\Enums\CourtType;
use App\Models\Court;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->customer = User::factory()->create();
});

test('the court list shows only active courts', function () {
    Court::factory()->count(2)->create(['is_active' => true]);
    Court::factory()->inactive()->create();

    $this->actingAs($this->customer)
        ->get(route('courts.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/courts')
            ->has('courts.data', 2)
        );
});

test('courts can be filtered by type', function () {
    Court::factory()->indoor()->create();
    Court::factory()->outdoor()->create();

    $this->actingAs($this->customer)
        ->get(route('courts.index', ['type' => CourtType::Indoor->value]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('courts.data', 1)
            ->where('courts.data.0.type', CourtType::Indoor->value)
        );
});

test('courts can be searched by name', function () {
    Court::factory()->create(['name' => 'Sunrise Arena']);
    Court::factory()->create(['name' => 'Downtown Court']);

    $this->actingAs($this->customer)
        ->get(route('courts.index', ['search' => 'Sunrise']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('courts.data', 1)
            ->where('courts.data.0.name', 'Sunrise Arena')
        );
});

test('an invalid type filter is rejected', function () {
    $this->actingAs($this->customer)
        ->get(route('courts.index', ['type' => 'rooftop']))
        ->assertSessionHasErrors('type');
});

test('the court detail page shows the court with correct pricing', function () {
    $court = Court::factory()->create([
        'name' => 'Premium Indoor',
        'hourly_rate' => 40000, // ₱400.00
        'is_active' => true,
    ]);

    $this->actingAs($this->customer)
        ->get(route('courts.show', $court))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/court-detail')
            ->where('court.data.name', 'Premium Indoor')
            ->where('court.data.hourly_rate_formatted', '₱400.00')
            ->where('court.data.opening_hours', fn ($hours) => is_string($hours))
        );
});

test('an inactive court is not directly accessible', function () {
    $court = Court::factory()->inactive()->create();

    $this->actingAs($this->customer)
        ->get(route('courts.show', $court))
        ->assertNotFound();
});

test('a guest cannot browse courts', function () {
    $this->get(route('courts.index'))->assertRedirect(route('login'));
});

test('a customer cannot modify courts through the admin routes', function () {
    $court = Court::factory()->create();

    $this->actingAs($this->customer)
        ->get(route('admin.courts.index'))
        ->assertForbidden();

    $this->actingAs($this->customer)
        ->patch(route('admin.courts.toggle-status', $court))
        ->assertForbidden();
});
