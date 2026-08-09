<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('customers are forbidden from the admin dashboard', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admins can visit the admin dashboard', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('admin/dashboard'));
});

test('the admin index redirects to the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/admin')
        ->assertRedirect('/admin/dashboard');
});
