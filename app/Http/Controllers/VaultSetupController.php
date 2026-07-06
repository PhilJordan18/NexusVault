<?php

namespace App\Http\Controllers;

use App\Http\Requests\VaultSetupRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

final class VaultSetupController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (! auth()->user()?->requiresClientVaultSetup()) {
            return redirect()->route('vault.unlock');
        }

        return view('auth.vault-setup');
    }

    public function store(VaultSetupRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->requiresClientVaultSetup()) {
            return $request->session()->has('vault_unlocked_at')
                ? redirect()->route('dashboard')
                : redirect()->route('vault.unlock');
        }

        $validated = $request->validated();

        $user->forceFill([
            'public_key' => $validated['public_key'],
            'private_key' => $validated['encrypted_private_key'],
            'private_nonce' => null,
            'encrypted_master_key' => null,
            'vault_key_envelope' => json_decode($validated['vault_key_envelope'], true, 512, JSON_THROW_ON_ERROR),
            'vault_recovery_envelope' => json_decode($validated['vault_recovery_envelope'], true, 512, JSON_THROW_ON_ERROR),
        ])->save();

        Session::forget(['masterKey', 'vault_legacy_unlock']);
        Session::put('vault_unlocked_at', now()->timestamp);
        Session::forget('url.intended');

        return redirect()->route('dashboard')->with('success', __('Encrypted vault created.'));
    }
}
