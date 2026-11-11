<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureMasterKey;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireMfa;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            if ($user->mfa_enabled && ! $request->session()->get('mfa_verified')) {
                return route('mfa.verify.login');
            }

            return route($user->requiresClientVaultSetup() ? 'vault.setup' : 'vault.unlock');
        });

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'master_key' => EnsureMasterKey::class,
            'mfa' => RequireMfa::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
