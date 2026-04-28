<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Services\Auth\Contracts\RegisterServiceInterface;

final class RegisterController extends Controller
{
    public function __construct(private readonly RegisterServiceInterface $service){}

    public function index() {return view('auth.register');}

    public function register(RegisterUserRequest $request)
    {
        $this->service->register($request->validated());

        return redirect()->route('verification.notice')->with('status', 'verification-link-sent');
    }
}
