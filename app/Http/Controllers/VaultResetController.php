<?php

namespace App\Http\Controllers;

use App\Http\Requests\VaultResetRequest;
use App\Models\Service;
use App\Models\Share;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

final class VaultResetController extends Controller
{
    public function store(VaultResetRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $vaultKeyEnvelope = json_decode($validated['vault_key_envelope'], true, 512, JSON_THROW_ON_ERROR);
        $vaultRecoveryEnvelope = json_decode($validated['vault_recovery_envelope'], true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($user, $validated, $vaultKeyEnvelope, $vaultRecoveryEnvelope): void {
            Share::where('from_user_id', $user->id)
                ->orWhere('to_user_id', $user->id)
                ->delete();

            Service::where('user_id', $user->id)
                ->orWhere('shared_user_id', $user->id)
                ->delete();

            $user->forceFill([
                'public_key' => $validated['public_key'],
                'private_key' => $validated['encrypted_private_key'],
                'private_nonce' => null,
                'encrypted_master_key' => null,
                'vault_key_envelope' => $vaultKeyEnvelope,
                'vault_recovery_envelope' => $vaultRecoveryEnvelope,
            ])->save();
        });

        Session::forget(['masterKey', 'vault_legacy_unlock']);
        Session::put('vault_unlocked_at', now()->timestamp);

        return redirect()->route('dashboard')->with('success', __('Vault reset. Your new vault is empty and unlocked in this browser.'));
    }
}
