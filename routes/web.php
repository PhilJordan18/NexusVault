<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MfaController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\WebAuthn\WebAuthnLoginController;
use App\Http\Controllers\WebAuthn\WebAuthnRegisterController;
use App\Models\Share;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('home');

Route::post('/locale', LocaleController::class)->name('locale.update');

// Password Routes
Route::post('/password/entropy', [PasswordController::class, 'entropy'])
    ->middleware('throttle:60,1')
    ->name('password.entropy');
Route::post('/password/generate', [PasswordController::class, 'generate'])
    ->middleware('throttle:30,1')
    ->name('password.generate');

// Regular Auth
Route::get('/register', [RegisterController::class, 'index'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:5,1');

Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'authenticateEmail'])
    ->middleware('throttle:10,1')
    ->name('login.authenticate.email');
Route::get('/login/password', [LoginController::class, 'password'])->name('login.password');
Route::post('/login/password', [LoginController::class, 'authenticate'])
    ->middleware('throttle:5,1')
    ->name('login.authenticate.password');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Forgot Password
Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:6,1')
    ->name('password.email');

// Reset Password
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
    ->middleware('throttle:6,1')
    ->name('password.update');

// OAuth
Route::get('/auth/github', [OAuthController::class, 'redirectGithub']);
Route::get('/auth/github/callback', [OAuthController::class, 'handleGithub']);
Route::get('/auth/google', [OAuthController::class, 'redirectGoogle']);
Route::get('/auth/google/callback', [OAuthController::class, 'handleGoogle']);

Route::middleware(['auth', 'mfa', 'master_key'])->group(function () {
    Route::post('/webauthn/register/options', [WebAuthnRegisterController::class, 'options']);
    Route::post('/webauthn/register', [WebAuthnRegisterController::class, 'register']);
});

Route::post('/webauthn/login/options', [WebAuthnLoginController::class, 'options'])->middleware('throttle:20,1');
Route::post('/webauthn/login', [WebAuthnLoginController::class, 'login'])->middleware('throttle:20,1');
Route::middleware(['auth', 'mfa', 'master_key'])->group(function () {
    Route::delete('/webauthn/credentials/{webauthn_credential}',
        [SettingsController::class, 'destroyPasskey']
    )->name('webauthn.destroy');
});

// Verify Email
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('message', 'Verification link sent!');
    })->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware('auth')->group(function () {
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('home')->with('success', 'Your Email has been verified!');
    })->middleware('signed')->name('verification.verify');
});

// MFA
Route::middleware('auth')->group(function () {
    Route::get('/mfa/setup', [MfaController::class, 'showSetup'])->name('mfa.setup');
    Route::post('/mfa/setup', [MfaController::class, 'verifySetup'])
        ->middleware('throttle:6,1')
        ->name('mfa.setup.verify');

    Route::get('/mfa/verify', fn () => view('auth.mfa.verify'))->name('mfa.verify.login');
    Route::post('/mfa/verify', [MfaController::class, 'verifyLogin'])
        ->middleware('throttle:6,1')
        ->name('mfa.verify');

    Route::post('/mfa/disable', [MfaController::class, 'disableMfa'])
        ->middleware(['mfa', 'master_key'])
        ->name('mfa.disable');
});

// Dashboard
Route::middleware(['auth', 'mfa', 'master_key'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/service/{service}', [DashboardController::class, 'show'])->name('dashboard.services');
});

Route::middleware(['auth', 'mfa', 'master_key'])->group(function () {
    Route::get('/api/services/suggest', [ServiceController::class, 'suggest'])->name('services.suggest');
});

// Settings
Route::middleware(['auth', 'mfa', 'master_key'])->prefix('settings')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('settings');
    Route::post('/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::post('/pfp', [SettingsController::class, 'updatePfp'])->name('settings.pfp.update');
    Route::delete('/account', [SettingsController::class, 'destroy'])->name('settings.account.destroy');
    Route::post('/theme', [SettingsController::class, 'updateTheme'])->name('settings.theme.update');
});
Route::middleware(['auth', 'mfa', 'master_key'])->group(function () {
    Route::get('/passkeys', [SettingsController::class, 'passkeys'])->name('passkeys.index');
});

// Active Sessions
Route::middleware(['auth', 'mfa', 'master_key'])->prefix('settings')->group(function () {
    Route::delete('/sessions/{sessionId}', [SettingsController::class, 'revokeSession'])
        ->name('settings.sessions.revoke');

    Route::post('/sessions/logout-all', [SettingsController::class, 'logoutAllOtherSessions'])
        ->name('settings.sessions.logout-all');
});

// Vault
Route::middleware(['auth', 'mfa', 'master_key'])->group(function () {
    Route::get('/search', [SearchController::class, 'search'])->name('search');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{name}', [ServiceController::class, 'show'])->name('services.show');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
});

Route::middleware(['auth', 'mfa', 'master_key'])->group(function () {

    Route::post('/shares', [ShareController::class, 'store'])->name('shares.store');
    Route::post('/shares/{share}/accept', [ShareController::class, 'accept'])->name('shares.accept');
    Route::post('/shares/{share}/reject', [ShareController::class, 'reject'])->name('shares.reject');
    Route::delete('/shares/{share}/revoke', [ShareController::class, 'revoke'])->name('shares.revoke');
    Route::get('/notifications', function () {
        $pendingShares = Share::with(['service', 'fromUser'])
            ->where('to_user_id', auth()->id())
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('rejected', false)
            ->latest()
            ->get();

        return view('notifications.index', compact('pendingShares'));
    })->name('notifications.index');
});
