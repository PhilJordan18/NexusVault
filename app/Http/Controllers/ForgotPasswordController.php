<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

final class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT && $status !== Password::INVALID_USER) {
            return back()->withErrors(['email' => __($status)]);
        }

        return back()->with('status', __('If an account exists for this email, we have sent a password reset link.'));
    }
}
