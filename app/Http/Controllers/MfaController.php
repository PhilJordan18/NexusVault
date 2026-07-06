<?php

namespace App\Http\Controllers;

use App\Http\Requests\MfaRequest;
use App\Models\User;
use App\Services\Auth\Contracts\MfaServiceInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Random\RandomException;
use SodiumException;

final class MfaController extends Controller
{
    public function __construct(private readonly MfaServiceInterface $service) {}

    public function showSetup(): View
    {
        $user = auth()->user();

        if (empty($user->totp_secret)) {
            $secret = $this->service->generateSecret();
            $user->update(['totp_secret' => $secret]);
        }

        $qrUrl = $this->service->getQrCodeUrl($user);

        return view('auth.mfa.setup', [
            'qrUrl' => $qrUrl,
            'totp_secret' => $user->totp_secret,
        ]);
    }

    public function verifySetup(MfaRequest $request): RedirectResponse
    {
        $user = $this->getUser();
        $request->validated();
        if ($this->service->enableMfa($user, $request->code)) {
            return redirect()->route('dashboard')->with('success', ('MFA successfully enabled'));
        }

        return back()->with('error', 'Code incorrect. Try again.');
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function verifyLogin(MfaRequest $request): RedirectResponse
    {
        $user = $this->getUser();
        $request->validated();

        if ($this->service->verifyCode($user, $request->code)) {
            session(['mfa_verified' => true]);

            return redirect()->route($user->requiresClientVaultSetup() ? 'vault.setup' : 'vault.unlock');
        }

        return back()->with('error', 'TOTP Code is incorrect. Try again.');
    }

    public function disableMfa(): RedirectResponse
    {
        $this->service->disableMfa($this->getUser());

        return redirect()->route('settings')->with('success', ('MFA successfully disabled'));
    }

    private function getUser(): Authenticatable|null|User
    {
        $user = auth()->user();

        return $user;
    }
}
