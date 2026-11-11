<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if ($user && $user->mfa_enabled && ! session('mfa_verified')) {
            return redirect()->route('mfa.verify.login');
        }

        return $next($request);
    }
}
