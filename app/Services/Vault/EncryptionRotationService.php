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
                $username = $service->username;
                $password = $service->password;
                $notes = $service->notes;

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
