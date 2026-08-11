<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class SocialAuthController extends Controller
{
    /**
     * OAuth providers the application accepts.
     *
     * @var array<int, string>
     */
    public const PROVIDERS = ['google'];

    /**
     * Redirect the user to the provider's OAuth consent screen.
     */
    public function redirect(string $provider): SymfonyRedirect
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the provider callback: link or create the account and log in.
     *
     * Matching is by provider identity first, then by email (so an existing
     * password account is linked rather than duplicated). Provider emails are
     * treated as verified.
     */
    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        try {
            $oauthUser = Socialite::driver($provider)->user();
        } catch (\Throwable) {
            // Covers a denied grant or an invalid/expired OAuth state.
            return $this->failed();
        }

        $email = $oauthUser->getEmail();

        if ($email === null) {
            return $this->failed('We could not read an email address from that account.');
        }

        $user = User::query()
            ->where('provider', $provider)
            ->where('provider_id', $oauthUser->getId())
            ->first()
            ?? User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = new User;
            $user->role = UserRole::Customer;
            $user->email_verified_at = now();
        }

        $user->fill([
            'name' => $user->name ?: ($oauthUser->getName() ?? $email),
            'email' => $email,
            'provider' => $provider,
            'provider_id' => $oauthUser->getId(),
            'avatar' => $oauthUser->getAvatar(),
        ])->save();

        Auth::login($user, remember: true);

        return redirect()->intended(
            $user->isAdmin() ? route('admin.dashboard') : route('dashboard'),
        );
    }

    /**
     * Bounce back to the login page with a friendly error.
     */
    private function failed(string $message = 'Unable to sign in with that provider. Please try again.'): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
