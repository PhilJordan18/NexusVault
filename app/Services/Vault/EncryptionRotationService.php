<?php

namespace App\Services\Vault;

use App\Models\Service;
use App\Services\Security\CryptoService;
use Illuminate\Support\Facades\DB;

final readonly class EncryptionRotationService
{
    public function __construct(private CryptoService $crypto) {}

    /**
     * Re‑encrypt all services of a user using an old master key and a new one.
     */
    public function reEncryptAllServicesForUser(int $userId, string $oldMasterKey, string $newMasterKey): void
    {
        $services = Service::where('user_id', $userId)->get();

        DB::transaction(function () use ($services, $oldMasterKey, $newMasterKey) {
            foreach ($services as $service) {
                // 🔥 Ne pas utiliser les accesseurs → on lit les colonnes brutes
                $rawUsername = $service->getRawOriginal('username');
                $rawPassword = $service->getRawOriginal('password');
                $rawNotes = $service->getRawOriginal('notes');

                $username = $this->crypto->decryptWithCustomKey(
                    $rawUsername, $service->username_iv, $service->username_tag, $oldMasterKey
                );
                $password = $this->crypto->decryptWithCustomKey(
                    $rawPassword, $service->password_iv, $service->password_tag, $oldMasterKey
                );
                $notes = $rawNotes
                    ? $this->crypto->decryptWithCustomKey(
                        $rawNotes, $service->notes_iv, $service->notes_tag, $oldMasterKey
                    )
                    : null;

                $encUsername = $this->crypto->encryptWithCustomKey($username, $newMasterKey);
                $encPassword = $this->crypto->encryptWithCustomKey($password, $newMasterKey);
                $encNotes = $notes ? $this->crypto->encryptWithCustomKey($notes, $newMasterKey) : null;

                $service->update([
                    'username'     => $encUsername['ciphertext'],
                    'username_iv'  => $encUsername['iv'],
                    'username_tag' => $encUsername['tag'],
                    'password'     => $encPassword['ciphertext'],
                    'password_iv'  => $encPassword['iv'],
                    'password_tag' => $encPassword['tag'],
                    'notes'        => $encNotes['ciphertext'] ?? null,
                    'notes_iv'     => $encNotes['iv'] ?? null,
                    'notes_tag'    => $encNotes['tag'] ?? null,
                ]);
            }
        });
    }
}
