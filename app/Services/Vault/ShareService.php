<?php

namespace App\Services\Vault;

use App\Models\Service;
use App\Models\Share;
use App\Models\User;
use App\Services\Auth\UserKeyService;
use App\Services\Security\CryptoService;
use App\Services\Vault\Contracts\ShareServiceInterface;

final readonly class ShareService implements ShareServiceInterface
{
    public function __construct(
        private CryptoService $cryptoService,
        private UserKeyService $userKeyService
    ) {}

    public function share(Service $service, string $recipientEmail): Share
    {
        $recipient = User::where('email', $recipientEmail)->firstOrFail();

        $publicKey = trim($recipient->public_key);
        if (empty($publicKey) || !str_contains($publicKey, 'BEGIN PUBLIC KEY')) {
            throw new \RuntimeException('La clé publique du destinataire est invalide.');
        }

        // 1. Générer une clé AES aléatoire (32 bytes)
        $aesKey = random_bytes(32);

        // 2. Chiffrer les données sensibles avec cette clé AES
        $sensitiveData = json_encode([
            'username' => $service->username,
            'password' => $service->password,
            'notes'    => $service->notes,
        ]);

        $encryptedData = $this->cryptoService->encryptWithCustomKey($sensitiveData, $aesKey);

        // 3. Chiffrer la clé AES avec la clé publique RSA du destinataire
        $encryptedAesKey = $this->cryptoService->encryptWithPublicKey($aesKey, $publicKey);

        $payload = [
            'encrypted_aes_key' => $encryptedAesKey,
            'encrypted_data'    => $encryptedData,
            'name'              => $service->name,
            'url'               => $service->url,
            'favicon'           => $service->favicon,
        ];

        return Share::create([
            'service_id'    => $service->id,
            'from_user_id'  => auth()->id(),
            'to_user_id'    => $recipient->id,
            'shared_data'   => json_encode($payload),
            'shared_at'     => now(),
        ]);
    }

    public function accept(Share $share): Service
    {
        if ($share->to_user_id !== auth()->id() || $share->accepted_at || $share->rejected) {
            abort(403);
        }

        $payload = json_decode($share->shared_data, true);

        // 1. Déchiffrer la clé AES avec la clé privée RSA du destinataire
        $privateKey = $this->userKeyService->getDecryptedPrivateKey(auth()->user());
        $aesKey = $this->cryptoService->decryptWithPrivateKey($payload['encrypted_aes_key'], $privateKey);

        // 2. Déchiffrer les données avec la clé AES
        $decryptedJson = $this->cryptoService->decryptWithCustomKey(
            $payload['encrypted_data']['ciphertext'],
            $payload['encrypted_data']['iv'],
            $payload['encrypted_data']['tag'],
            $aesKey
        );

        $data = json_decode($decryptedJson, true);

        // --- RECHIFFREMENT AVEC LA MASTER KEY DU DESTINATAIRE ---
        $encUsername = $this->cryptoService->encryptWithMasterKey($data['username']);
        $encPassword = $this->cryptoService->encryptWithMasterKey($data['password']);
        $encNotes = !empty($data['notes']) ? $this->cryptoService->encryptWithMasterKey($data['notes']) : null;

        $service = Service::create([
            'user_id'        => auth()->id(),
            'name'           => $payload['name'],
            'url'            => $payload['url'],
            'favicon'        => $payload['favicon'],
            // Champs chiffrés
            'username'       => $encUsername['ciphertext'],
            'username_iv'    => $encUsername['iv'],
            'username_tag'   => $encUsername['tag'],
            'password'       => $encPassword['ciphertext'],
            'password_iv'    => $encPassword['iv'],
            'password_tag'   => $encPassword['tag'],
            'notes'          => $encNotes ? $encNotes['ciphertext'] : null,
            'notes_iv'       => $encNotes ? $encNotes['iv'] : null,
            'notes_tag'      => $encNotes ? $encNotes['tag'] : null,
            'shared_user_id' => $share->from_user_id,
        ]);

        $share->update(['accepted_at' => now()]);
        return $service;
    }

    public function reject(Share $share): void
    {
        if ($share->to_user_id !== auth()->id()) abort(403);
        $share->update(['rejected' => true]);
    }
}
