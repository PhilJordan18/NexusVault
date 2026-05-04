<?php

namespace App\Http\Controllers\WebAuthn;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;
use App\Services\Auth\UserKeyService;

use function response;

readonly class WebAuthnLoginController
{
    public function __construct(private UserKeyService $userKeyService) {}
    /**
     * Returns the challenge to assertion.
     */
    public function options(AssertionRequest $request): Responsable
    {
        return $request->toVerify($request->validate(['email' => 'sometimes|email|string']));
    }

    /**
     * Log the user in.
     */
    public function login(AssertedRequest $request): JsonResponse
    {
        $success = $request->login();

        if (!$success) {
            return response()->json(['message' => 'Authentication failed'], 422);
        }

        $user = auth()->user();
        $this->userKeyService->storeMasterKey($user);

        if ($user->mfa_enabled) {
            return response()->json(['redirect' => route('mfa.verify.login')]);
        }

        return response()->json(['redirect' => route('dashboard')]);
    }
}
