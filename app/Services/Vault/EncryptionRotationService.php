<?php

namespace App\Services\Vault;

use App\Models\Service;
use App\Services\Security\CryptoService;
use Illuminate\Support\Facades\DB;

final readonly class EncryptionRotationService
{
    public function __construct(private CryptoService $crypto) {}

    /**
     * Re‑encrypt all services of a user with a new master key.
     */
    public function reEncryptAllServicesForUser(int $userId, string $newMasterKey): void
    {
        $services = Service::where('user_id', $userId)->get();

        DB::transaction(function () use ($services, $newMasterKey) {
            foreach ($services as $service) {
                // 1. Déchiffre les champs sensibles avec l’ancienne clé (celle actuellement en session)
                $username = $this->crypto->decryptWithMasterKey(
                    $service->username,
                    $service->username_iv,
                    $service->username_tag
                );
                $password = $this->crypto->decryptWithMasterKey(
                    $service->password,
                    $service->password_iv,
                    $service->password_tag
                );
                $notes = null;
                if ($service->notes) {
                    $notes = $this->crypto->decryptWithMasterKey(
                        $service->notes,
                        $service->notes_iv,
                        $service->notes_tag
                    );
                }

                // 2. Re‑chiffre avec la nouvelle clé (en utilisant encryptWithCustomKey pour réutiliser une clé qu’on fournit)
                $encUsername = $this->crypto->encryptWithCustomKey($username, $newMasterKey);
                $encPassword = $this->crypto->encryptWithCustomKey($password, $newMasterKey);
                $encNotes = $notes ? $this->crypto->encryptWithCustomKey($notes, $newMasterKey) : null;

                // 3. Mise à jour directe (sans déclencher les accesseurs)
                $service->update([
                    'username'     => $encUsername['ciphertext'],
                    'username_iv'  => $encUsername['iv'],
                    'username_tag' => $encUsername['tag'],
                    'password'     => $encPassword['ciphertext'],
                    'password_iv'  => $encPassword['iv'],
                    'password_tag' => $encPassword['tag'],
                    'notes'        => $encNotes ? $encNotes['ciphertext'] : null,
                    'notes_iv'     => $encNotes ? $encNotes['iv'] : null,
                    'notes_tag'    => $encNotes ? $encNotes['tag'] : null,
                ]);
            }
        });
    }
}
