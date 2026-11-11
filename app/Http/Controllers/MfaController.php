<?php

namespace App\Http\Controllers;

use App\Http\Requests\DisableMfaRequest;
use App\Http\Requests\MfaRequest;
use App\Models\User;
use App\Services\Auth\Contracts\MfaServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Random\RandomException;
use SodiumException;

final class MfaController extends Controller
{
    private const PENDING_SECRET_SESSION_KEY = 'mfa_pending_secret';

    public function __construct(private readonly MfaServiceInterface $service) {}

    /**
     * Show the enrollment page with a secret that lives in the session until it is verified.
     * A confirmed secret is never shown again: re-enrollment requires disabling MFA first.
     */
    public function showSetup(): View|RedirectResponse
    {
        $user = $this->getUser();

        if ($user->mfa_enabled) {
            return redirect()->route('settings')->with('info', __('Two-factor authentication is already enabled.'));
        }

        $pendingSecret = Session::get(self::PENDING_SECRET_SESSION_KEY);

        if (empty($pendingSecret)) {
            $pendingSecret = $this->service->generateSecret();
            Session::put(self::PENDING_SECRET_SESSION_KEY, $pendingSecret);
        }

        return view('auth.mfa.setup', [
            'totp_secret' => $pendingSecret,
        ]);
    }

    public function qrCode(): Response
    {
        $user = $this->getUser();
        $pendingSecret = Session::get(self::PENDING_SECRET_SESSION_KEY);

        abort_if($user->mfa_enabled || empty($pendingSecret), 404);

        $qrCode = $this->service->getQrCodeImage($user, $pendingSecret);

        return response($qrCode['contents'], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Content-Type' => $qrCode['mime_type'],
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function verifySetup(MfaRequest $request): RedirectResponse
    {
        $user = $this->getUser();
        $pendingSecret = Session::get(self::PENDING_SECRET_SESSION_KEY);

        if ($user->mfa_enabled || empty($pendingSecret)) {
            return redirect()->route('mfa.setup');
        }

        if ($this->service->enableMfa($user, $pendingSecret, $request->validated()['code'])) {
            Session::forget(self::PENDING_SECRET_SESSION_KEY);
            Session::put('mfa_verified', true);

            return redirect()->route('dashboard')->with('success', __('MFA successfully enabled'));
        }

        return back()->with('error', __('Code incorrect. Try again.'));
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function verifyLogin(MfaRequest $request): RedirectResponse
    {
        $user = $this->getUser();

        if ($this->service->verifyCode($user, $request->validated()['code'])) {
            Session::put('mfa_verified', true);

            return redirect()->route($user->requiresClientVaultSetup() ? 'vault.setup' : 'vault.unlock');
        }

        return back()->with('error', __('TOTP Code is incorrect. Try again.'));
    }

    public function disableMfa(DisableMfaRequest $request): RedirectResponse
    {
        $request->validated();
        $this->service->disableMfa($this->getUser());

        return redirect()->route('settings')->with('success', __('MFA successfully disabled'));
    }

    private function getUser(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
