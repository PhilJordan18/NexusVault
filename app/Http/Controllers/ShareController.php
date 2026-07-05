<?php

namespace App\Http\Controllers;

use App\Exceptions\ShareException;
use App\Http\Requests\ShareRequest;
use App\Mappers\ShareMapper;
use App\Models\Share;
use App\Services\Vault\Contracts\ShareServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ShareController extends Controller
{
    public function __construct(private readonly ShareServiceInterface $shareService) {}

    public function prepare(ShareRequest $request): JsonResponse
    {
        try {
            $sharedData = ShareMapper::fromRequest($request->validated());

            return response()->json($this->shareService->prepareClientEncryptedShare($sharedData));
        } catch (ShareException $exception) {
            return response()->json(['message' => __($exception->getMessage())], 422);
        }
    }

    public function store(ShareRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $validated = $request->validated();
            $sharedData = ShareMapper::fromRequest($validated);

            $share = $request->boolean('client_encrypted')
                ? $this->shareService->shareClientEncrypted($sharedData, $validated)
                : $this->shareService->share($sharedData);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => __('Share sent successfully!'),
                    'share' => [
                        'id' => $share->id,
                        'status' => 'Pending',
                        'shared_at' => $share->shared_at?->diffForHumans(),
                    ],
                ]);
            }

            return back()->with('success', __('Share sent successfully!'));
        } catch (ShareException $exception) {
            if ($request->wantsJson()) {
                return response()->json(['message' => __($exception->getMessage())], 422);
            }

            return back()->with('error', __($exception->getMessage()));
        }
    }

    public function accept(Request $request, Share $share): JsonResponse|RedirectResponse
    {
        try {
            if ($this->isClientEncryptedShare($share)) {
                $validated = $this->isClientEncryptedSyncShare($share)
                    ? $request->validate([
                        'client_encrypted' => ['required', 'boolean'],
                        'shared_key_envelope' => ['required', 'array'],
                        'shared_key_envelope.version' => ['required', 'integer'],
                        'shared_key_envelope.algorithm' => ['required', 'string'],
                        'shared_key_envelope.keySource' => ['required', 'string'],
                        'shared_key_envelope.ciphertext' => ['required', 'string'],
                        'shared_key_envelope.iv' => ['required', 'string', 'size:24'],
                        'shared_key_envelope.tag' => ['required', 'string', 'size:32'],
                    ])
                    : $request->validate([
                        'client_encrypted' => ['required', 'boolean'],
                        'username' => ['required', 'string'],
                        'username_iv' => ['required', 'string', 'size:24'],
                        'username_tag' => ['required', 'string', 'size:32'],
                        'password' => ['required', 'string'],
                        'password_iv' => ['required', 'string', 'size:24'],
                        'password_tag' => ['required', 'string', 'size:32'],
                        'notes' => ['nullable', 'string'],
                        'notes_iv' => ['nullable', 'required_with:notes', 'string', 'size:24'],
                        'notes_tag' => ['nullable', 'required_with:notes', 'string', 'size:32'],
                    ]);

                $this->shareService->acceptClientEncrypted($share, $validated);
            } else {
                $this->shareService->accept($share);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => __('Service added to your vault with success!'),
                    'redirect' => route('dashboard'),
                ]);
            }

            return redirect()->route('dashboard')->with('success', __('Service added to your vault with success!'));
        } catch (ShareException $exception) {
            if ($request->wantsJson()) {
                return response()->json(['message' => __($exception->getMessage())], 422);
            }

            return back()->with('error', __($exception->getMessage()));
        }
    }

    public function reject(Share $share): RedirectResponse
    {
        $this->shareService->reject($share);

        return redirect()->route('dashboard')->with('info', __('Sharing rejected!'));
    }

    public function revoke(Share $share): JsonResponse|RedirectResponse
    {
        $this->shareService->revoke($share);

        return request()->expectsJson()
            ? response()->json(['message' => __('Shared access revoked.')])
            : back()->with('success', __('Shared access revoked.'));
    }

    private function isClientEncryptedShare(Share $share): bool
    {
        $payload = json_decode($share->shared_data, true);

        return in_array($payload['mode'] ?? null, ['client-encrypted', 'client-encrypted-sync'], true);
    }

    private function isClientEncryptedSyncShare(Share $share): bool
    {
        $payload = json_decode($share->shared_data, true);

        return ($payload['mode'] ?? null) === 'client-encrypted-sync';
    }
}
