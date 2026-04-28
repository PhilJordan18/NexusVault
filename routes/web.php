<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MfaController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\WebAuthn\WebAuthnLoginController;
use App\Http\Controllers\WebAuthn\WebAuthnRegisterController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// routes/web.php
Route::get('/', function () {
    return view('landing');
})->name('home');

// Password Routes
Route::post('/password/entropy', [PasswordController::class, 'entropy'])->name('password.entropy');
Route::post('/password/generate', [PasswordController::class, 'generate'])->name('password.generate');

// Regular Auth
Route::get('/register', [RegisterController::class, 'index'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'authenticateEmail'])->name('login.authenticate.email');
Route::get('/login/password', [LoginController::class, 'password'])->name('login.password');
Route::post('/login/password', [LoginController::class, 'authenticate'])->name('login.authenticate.password');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

//OAuth
Route::get('/auth/github', [OAuthController::class, 'redirectGithub']);
Route::get('/auth/github/callback', [OAuthController::class, 'handleGithub']);
Route::get('/auth/google', [OAuthController::class, 'redirectGoogle']);
Route::get('/auth/google/callback', [OAuthController::class, 'handleGoogle']);

//MFA
Route::middleware('auth')->group(function () {
    Route::get('/mfa/setup', [MfaController::class, 'showSetup'])->name('mfa.setup');
    Route::post('/mfa/setup', [MfaController::class, 'verifySetup'])->name('mfa.setup.verify');

    Route::get('/mfa/verify', fn() => view('auth.mfa.verify'))->name('mfa.verify.login');
    Route::post('/mfa/verify', [MfaController::class, 'verifyLogin'])->name('mfa.verify');

    Route::post('/mfa/disable', [MfaController::class, 'disableMfa'])->name('mfa.disable');
});


// Enregistrement d'une nouvelle Passkey (depuis les settings)
Route::middleware('auth')->group(function () {
    Route::post('/webauthn/register/options', [WebAuthnRegisterController::class, 'options']);   // ← CHANGÉ EN POST
    Route::post('/webauthn/register', [WebAuthnRegisterController::class, 'register']);
});

// Login avec Passkey
Route::post('/webauthn/login/options', [WebAuthnLoginController::class, 'options']);
Route::post('/webauthn/login', [WebAuthnLoginController::class, 'login']);

//Verify Email
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('home')->with('success', 'Your Email has been verified!');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', 'Verification link sent!');
    })->middleware('throttle:6,1')->name('verification.send');
});


//Dashboard
Route::middleware(['auth', 'master_key','mfa'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/service/{service}', [DashboardController::class, 'show'])->name('dashboard.services');
});

// Settings
Route::middleware(['auth', 'master_key','mfa'])->prefix('settings')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('settings');
    Route::post('/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::post('/pfp', [SettingsController::class, 'updatePfp'])->name('settings.pfp.update');
    Route::delete('/account', [SettingsController::class, 'destroy'])->name('settings.account.destroy');
});


//Vault
Route::middleware(['auth', 'master_key','mfa'])->group(function () {
    Route::get('/dashboard', [ServiceController::class, 'index'])->name('dashboard');

    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{name}', [ServiceController::class, 'show'])->name('services.show');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
});

Route::middleware(['auth', 'master_key','mfa'])->group(function () {

    Route::post('/shares', [ShareController::class, 'store'])->name('shares.store');
    Route::post('/shares/{share}/accept', [ShareController::class, 'accept'])->name('shares.accept');
    Route::post('/shares/{share}/reject', [ShareController::class, 'reject'])->name('shares.reject');
    Route::get('/notifications', function () {
        $pendingShares = \App\Models\Share::with(['service', 'fromUser'])
            ->where('to_user_id', auth()->id())
            ->whereNull('accepted_at')
            ->where('rejected', false)
            ->latest()
            ->get();

        return view('notifications.index', compact('pendingShares'));
    })->name('notifications.index');
});
