<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdatePfpRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SettingsController extends Controller
{
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
        $user->update(['password' => bcrypt($request->new_password)]);
        app('App\Services\Auth\Contracts\UserKeyServiceInterface')->storeMasterKey($user, $request->new_password);
        return back()->with('success', 'Your password has been updated.');
    }


    public function destroy(): RedirectResponse {
        $user = auth()->user();
        $user->delete();
        auth()->logout();
        return redirect()->route('login')->with('success', 'Your account has been deleted.');
    }
}
