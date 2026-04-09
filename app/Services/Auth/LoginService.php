<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\LoginServiceInterface;
use App\Services\Auth\Contracts\UserKeyServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

final readonly class LoginService implements LoginServiceInterface
{
    public function __construct(private UserKeyServiceInterface $service) {}
    public function login(array $credentials): RedirectResponse
    {
        $user = User::where('email', $credentials['email'])->first();
        if(!$user || !Hash::check($credentials['password'], $user->password)){
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }
        if (!$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['email' => 'Please verify your email']);
        }
        Auth::login($user);
        Session::regenerate();
        $this->service->storeMasterKey($user, $credentials['password']);
        return redirect()->intended('/dashboard');
    }

    public function logout(): void
    {
        Session::forget('masterKey');
        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();
    }
}
