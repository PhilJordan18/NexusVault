<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdatePfpRequest;
use App\Models\Service;
use App\Models\Share;
use App\Services\Security\CryptoService;
use App\Services\Vault\EncryptionRotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Laragear\WebAuthn\Models\WebAuthnCredential;

final class SettingsController extends Controller
{
    public function __construct(private readonly EncryptionRotationService $rotationService, private readonly CryptoService $crypto) {}

    public function index(): View { return view('settings.index'); }

    public function updatePfp(UpdatePfpRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $path = $request->file('pfp')->store('pfp', 'public');
        $user->update(['pfp' => $path]);
        return back()->with('success', 'Your pfp has been updated.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = auth()->user();
        if ($user->is_oauth) {
            abort(403, 'OAuth users cannot change their password.');
        }

        $validated = $request->validated();

        $newMasterKey = $this->crypto->deriveMasterKey($validated['new_password'], $user->salt);
        $this->rotationService->reEncryptAllServicesForUser($user->id, $newMasterKey);
        $user->password = Hash::make($validated['new_password']);
        $user->encrypted_master_key = Crypt::encrypt($newMasterKey);
        $user->save();

        Session::put('masterKey', base64_encode($newMasterKey));

        return back()->with('success', 'Your password and encryption keys have been updated successfully.');
    }

    public function revokeSession(Request $request, string $sessionId): JsonResponse
    {
        $userId = auth()->id();
        $currentSessionId = session()->getId();

        $deleted = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $userId)
            ->delete();

        if ($deleted) {
            if ($sessionId === $currentSessionId) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'message' => 'Session revoked. You have been logged out.',
                    'redirect' => route('login')
                ]);
            }

            return response()->json(['message' => 'Session revoked successfully.']);
        }

        return response()->json(['message' => 'Session not found.'], 404);
    }

    public function logoutAllOtherSessions(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $currentSessionId = session()->getId();

        $deleted = DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        return response()->json([
            'message' => 'Logged out from all other devices.',
            'deleted_count' => $deleted
        ]);
    }


    public function destroy(): RedirectResponse
    {
        $user = auth()->user();

        DB::table('sessions')->where('user_id', $user->id)->delete();

        Service::where('shared_user_id', $user->id)->delete();

        $user->services()->delete();

        Share::where('from_user_id', $user->id)
            ->orWhere('to_user_id', $user->id)
            ->delete();

        $user->webAuthnCredentials()->delete();

        $user->delete();

        auth()->logout();

        return redirect()->route('login')->with('success', 'Your account has been deleted.');
    }

    public function destroyPasskey(WebAuthnCredential $webauthnCredential)
    {
        if ($webauthnCredential->user_id !== auth()->id()) {
            abort(403);
        }
        $webauthnCredential->delete();
        return back()->with('success', 'Passkey deleted.');
    }

    public function passkeys()
    {
        return view('passkey.index');
    }
}
