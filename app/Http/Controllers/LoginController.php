<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginEmailRequest;
use App\Http\Requests\LogUserRequest;
use App\Services\Auth\Contracts\LoginServiceInterface;
use Illuminate\Http\RedirectResponse;

final class LoginController extends Controller
{
    public function __construct(private readonly LoginServiceInterface $service){}

    public function index() {return view('auth.login');}
    public function password() {return view('auth.login-password');}
    public function authenticateEmail(LoginEmailRequest $request):RedirectResponse { return $this->service->authenticate($request->validated()); }
    public function authenticate(LogUserRequest $request):RedirectResponse { return $this->service->authenticate($request->validated()); }
    public function logout(): RedirectResponse {
        $this->service->logout();
        return redirect()->route('login');
    }

}
