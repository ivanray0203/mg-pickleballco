<?php

use App\Models\Equipment;
use Inertia\Testing\AssertableInertia;

test('the landing page previews active equipment, cheapest first', function () {
    Equipment::factory()->create(['name' => 'Court Shoes', 'hourly_rate' => 4000]);
    Equipment::factory()->create(['name' => 'Ball Set', 'hourly_rate' => 3000]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('welcome')
            ->has('equipment.data', 2)
            ->where('equipment.data.0.name', 'Ball Set')
            ->where('equipment.data.0.hourly_rate_formatted', '₱30.00')
            ->where('equipment.data.1.name', 'Court Shoes')
        );
});

test('inactive equipment is never advertised', function () {
    Equipment::factory()->inactive()->create(['name' => 'Retired Paddle']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('equipment.data', 0));
});

test('the equipment preview is capped', function () {
    Equipment::factory()->count(8)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('equipment.data', 4));
});
