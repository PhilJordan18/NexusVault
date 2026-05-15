<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdatePfpRequest;
use App\Http\Requests\UpdateThemeRequest;
use App\Services\Auth\AccountDeletionService;
use App\Services\Auth\ChangePasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Laragear\WebAuthn\Models\WebAuthnCredential;

final class SettingsController extends Controller
{
    public function __construct(private readonly ChangePasswordService $changePasswordService, private readonly AccountDeletionService $accountDeletionService) {}

    public function index(): View
    {
        return view('settings.index');
    }

    public function updatePfp(UpdatePfpRequest $request): RedirectResponse
    {
        $path = $request->file('pfp')->store('pfp', 'public');
        auth()->user()->update(['pfp' => $path]);

        return back()->with('success', 'Your profile picture has been updated.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->changePasswordService->change(auth()->user(), $request->validated()['new_password']);

        return back()->with('success', 'Your password and encryption keys have been updated successfully.');
    }

    public function revokeSession(string $sessionId): JsonResponse
    {
        $userId = auth()->id();
        $currentSessionId = session()->getId();

        $deleted = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $userId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Session not found.'], 404);
        }

        if ($sessionId === $currentSessionId) {
            auth()->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return response()->json([
                'message' => 'Session revoked. You have been logged out.',
                'redirect' => route('login')
            ]);
        }

        return response()->json(['message' => 'Session revoked successfully.']);
    }

    public function logoutAllOtherSessions(): JsonResponse
    {
        $deleted = DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();

        return response()->json([
            'message' => 'Logged out from all other devices.',
            'deleted_count' => $deleted
        ]);
    }

    public function destroy(): RedirectResponse
    {
        $this->accountDeletionService->delete(auth()->user());
        auth()->logout();

        return redirect()->route('login')->with('success', 'Your account has been deleted.');
    }

    public function destroyPasskey(WebAuthnCredential $webauthnCredential): RedirectResponse
    {
        if ($webauthnCredential->user_id !== auth()->id()) {
            abort(403);
        }

        $webauthnCredential->delete();

        return back()->with('success', 'Passkey deleted.');
    }

    public function passkeys(): View
    {
        return view('passkey.index');
    }

    public function updateTheme(UpdateThemeRequest $request): JsonResponse
    {
        $user = auth()->user();
        $user->theme = $request->theme;
        $user->save();

        return response()->json(['success' => true, 'theme' => $user->theme]);
    }
}
