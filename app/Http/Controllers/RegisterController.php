<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Services\Auth\Contracts\RegisterServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

final class RegisterController extends Controller
{
    public function __construct(private readonly RegisterServiceInterface $service) {}

    public function index(): View
    {
        return view('auth.register');
    }

    public function validateRegistration(Request $request): JsonResponse
    {
        Validator::make($request->all(), RegisterUserRequest::accountRules())->validate();

        return response()->json(['valid' => true]);
    }

    public function register(RegisterUserRequest $request): RedirectResponse
    {
        $user = $this->service->register($request->validated());
        Auth::login($user);

        return redirect()->route('verification.notice')->with('status', 'verification-link-sent');
    }
}
