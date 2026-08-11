<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('a guest is redirected to the provider', function () {
    Socialite::fake('google');

    $this->get(route('oauth.redirect', 'google'))->assertRedirect();
});

test('an unsupported provider is rejected', function (string $provider) {
    $this->get(route('oauth.redirect', $provider))->assertNotFound();
    $this->get(route('oauth.callback', $provider))->assertNotFound();
})->with(['myspace', 'facebook']);

test('a new user is created and logged in via google', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Jordan Cruz',
        'email' => 'jordan@example.com',
    ]));

    $response = $this->get(route('oauth.callback', 'google'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    $user = User::firstWhere('email', 'jordan@example.com');

    expect($user)->not->toBeNull()
        ->and($user->provider)->toBe('google')
        ->and($user->provider_id)->toBe('google-123')
        ->and($user->role)->toBe(UserRole::Customer)
        ->and($user->password)->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull();
});

test('an existing account with the same email is linked, not duplicated', function () {
    $existing = User::factory()->create([
        'email' => 'existing@example.com',
        'name' => 'Existing User',
    ]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-999',
        'name' => 'Existing User',
        'email' => 'existing@example.com',
    ]));

    $this->get(route('oauth.callback', 'google'))->assertRedirect(route('dashboard'));

    $existing->refresh();

    expect(User::count())->toBe(1)
        ->and($existing->provider)->toBe('google')
        ->and($existing->provider_id)->toBe('google-999');

    $this->assertAuthenticatedAs($existing);
});

test('an admin logging in via social lands on the admin dashboard', function () {
    User::factory()->admin()->create(['email' => 'admin@example.com']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-admin',
        'name' => 'Admin',
        'email' => 'admin@example.com',
    ]));

    $this->get(route('oauth.callback', 'google'))
        ->assertRedirect(route('admin.dashboard'));
});

test('a returning social user is matched by provider identity', function () {
    $user = User::factory()->create([
        'email' => 'returning@example.com',
        'provider' => 'google',
        'provider_id' => 'google-repeat',
    ]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-repeat',
        'name' => 'Returning',
        'email' => 'returning@example.com',
    ]));

    $this->get(route('oauth.callback', 'google'))->assertRedirect(route('dashboard'));

    expect(User::count())->toBe(1);
    $this->assertAuthenticatedAs($user);
});
