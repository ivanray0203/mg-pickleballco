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

        $this->app->singleton(PaymentGateway::class, function ($app): PaymentGateway {
            $driver = config('services.payment.driver', 'paymongo');
            $settings = $app->make(AppSettings::class);

            return match ($driver) {
                // Credentials come from the settings store, which falls back to
                // config/services.php when an admin has not set them.
                'paymongo' => new PayMongoGateway([
                    'secret_key' => $settings->paymongoSecretKey(),
                    'webhook_secret' => $settings->paymongoWebhookSecret(),
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
