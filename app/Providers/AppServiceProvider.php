<?php

namespace App\Providers;

use App\Services\Auth\Contracts\LoginServiceInterface;
use App\Services\Auth\Contracts\MfaServiceInterface;
use App\Services\Auth\Contracts\OAuthServiceInterface;
use App\Services\Auth\Contracts\RegisterServiceInterface;
use App\Services\Auth\Contracts\UserKeyServiceInterface;
use App\Services\Auth\LoginService;
use App\Services\Auth\MfaService;
use App\Services\Auth\OAuthService;
use App\Services\Auth\RegisterService;
use App\Services\Auth\UserKeyService;
use App\Services\Vault\Contracts\ServiceServiceInterface;
use App\Services\Vault\Contracts\ShareServiceInterface;
use App\Services\Vault\ServiceService;
use App\Services\Vault\ShareService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginServiceInterface::class, LoginService::class);
        $this->app->bind(RegisterServiceInterface::class, RegisterService::class);
        $this->app->bind(OAuthServiceInterface::class, OAuthService::class);
        $this->app->bind(UserKeyServiceInterface::class, UserKeyService::class);
        $this->app->bind(MfaServiceInterface::class, MfaService::class);
        $this->app->bind(ServiceServiceInterface::class, ServiceService::class);
        $this->app->bind(ShareServiceInterface::class, ShareService::class);
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
