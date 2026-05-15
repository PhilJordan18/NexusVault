<?php

namespace App\Services\Auth;

use App\DTOs\Auth\LoginData;
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
    public function __construct(private UserKeyServiceInterface $userKeyService) {}

    public function authenticate(array $credentials): RedirectResponse
    {
        $loginData = $this->createLoginData($credentials);

        if ($this->isPasswordMissing($loginData)) {
            return $this->redirectToPasswordPage($loginData->email);
        }

        return $this->login($loginData);
    }

    public function login(LoginData $data): RedirectResponse
    {
        $user = $this->findUserByEmail($data->email);

        $this->validateCredentials($user, $data->password);
        $this->ensureEmailIsVerified($user);

        $this->performLogin($user);
        $this->storeMasterKey($user, $data->password);

        return $this->redirectAfterLogin($user);
    }

    public function logout(): void
    {
        Session::forget('masterKey');
        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();
    }

    // ==================== PRIVATE HELPERS ====================

    private function createLoginData(array $credentials): LoginData
    {
        return new LoginData(
            email: $credentials['email'],
            password: $credentials['password'] ?? null
        );
    }

    private function isPasswordMissing(LoginData $data): bool
    {
        return empty($data->password);
    }

    private function redirectToPasswordPage(string $email): RedirectResponse
    {
        return redirect()->route('login.password', ['email' => $email]);
    }

    private function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    private function validateCredentials(?User $user, ?string $password): void
    {
        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }
    }

    private function ensureEmailIsVerified(User $user): void
    {
        if (!$user->hasVerifiedEmail()) {
            redirect()->route('verification.notice')
                ->with('status', 'verification-link-sent')
                ->throwResponse();
        }
    }

    private function performLogin(User $user): void
    {
        Auth::login($user);
        Session::regenerate();
    }

    private function storeMasterKey(User $user, string $password): void
    {
        $this->userKeyService->storeMasterKey($user, $password);
    }

    private function redirectAfterLogin(User $user): RedirectResponse
    {
        if ($user->mfa_enabled) {
            return redirect()->route('mfa.verify.login');
        }

        return redirect()->intended('/dashboard');
    }
}
