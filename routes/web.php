<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Password Routes
Route::post('/password/entropy', [PasswordController::class, 'entropy'])->name('password.entropy');
Route::post('/password/generate', [PasswordController::class, 'generate'])->name('password.generate');

// Regular Auth
Route::get('/register', [RegisterController::class, 'index'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate']);
Route::post('/logout', [LoginController::class, 'logout']);

//OAuth
Route::get('/auth/github', [OAuthController::class, 'redirectGithub']);
Route::get('/auth/github/callback', [OAuthController::class, 'handleGithub']);
Route::get('/auth/google', [OAuthController::class, 'redirectGoogle']);
Route::get('/auth/google/callback', [OAuthController::class, 'handleGoogle']);
