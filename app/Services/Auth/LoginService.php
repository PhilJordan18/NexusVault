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
        if ($user->mfa_enabled) {
            return redirect()->route('mfa.verify.login');
        }
        return redirect()->intended('/dashboard');
    }

    public function authenticate(array $credentials): RedirectResponse
    {
        if (!isset($credentials['password']) || empty($credentials['password'])) {
            return redirect()->route('login.password', ['email' => $credentials['email']]);
        }
        return $this->login($credentials);
    }

    public function logout(): void
    {
        Session::forget('masterKey');
        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();
    }
}
