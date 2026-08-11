<?php

namespace App\Providers;

use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PayMongoGateway;
use App\Settings\AppSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AppSettings::class);

        $this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
            $driver = config('services.payment.driver', 'paymongo');

            return match ($driver) {
                'paymongo' => new PayMongoGateway([
                    'secret_key' => config('services.paymongo.secret_key'),
                    'webhook_secret' => config('services.paymongo.webhook_secret'),
                    'success_url' => config('services.paymongo.success_url'),
                    'cancel_url' => config('services.paymongo.cancel_url'),
                ]),
                default => throw new InvalidArgumentException("Unsupported payment driver [{$driver}]."),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
