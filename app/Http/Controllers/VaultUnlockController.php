<?php

namespace App\Http\Controllers;

use App\Http\Requests\VaultUnlockRequest;
use App\Services\Auth\Contracts\UserKeyServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

final class VaultUnlockController extends Controller
{
    public function __construct(private readonly UserKeyServiceInterface $userKeyService) {}

    public function show(): View|RedirectResponse
    {
        if (auth()->user()?->requiresClientVaultSetup()) {
            return redirect()->route('vault.setup');
        }

        if (Session::has('masterKey')) {
            return redirect()->intended('/dashboard');
        }

        return view('auth.vault-unlock', [
            'allowsLegacyUnlock' => (bool) auth()->user()?->is_oauth,
        ]);
    }

    public function unlock(VaultUnlockRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($user->usesClientSideVault()) {
            if (! $request->boolean('client_unlocked')) {
                throw ValidationException::withMessages([
                    'vault_password' => __('Unlock your vault in this browser to continue.'),
                ]);
            }
        } elseif (! empty($validated['vault_password'])) {
            try {
                $this->userKeyService->storeMasterKey($user, $validated['vault_password']);
                $this->userKeyService->getDecryptedPrivateKey($user);
            } catch (RuntimeException) {
                Session::forget(['masterKey', 'vault_unlocked_at', 'vault_legacy_unlock']);

                throw ValidationException::withMessages([
                    'vault_password' => __('The vault password is incorrect.'),
                ]);
            }
        } elseif ($request->boolean('legacy_unlock') && $user->is_oauth) {
            $this->userKeyService->storeMasterKey($user);
            Session::put('vault_legacy_unlock', true);
        } else {
            throw ValidationException::withMessages([
                'vault_password' => __('Enter your vault password to continue.'),
            ]);
        }

        Session::put('vault_unlocked_at', now()->timestamp);

        return redirect()->intended('/dashboard')->with('success', __('Vault unlocked.'));
    }

    public function lock(): RedirectResponse
    {
        Session::forget(['masterKey', 'vault_unlocked_at', 'vault_legacy_unlock']);

        return redirect()->route('vault.unlock')->with('success', __('Vault locked.'));
    }
}
