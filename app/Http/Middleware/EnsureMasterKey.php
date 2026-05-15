<?php

namespace App\Http\Middleware;

use App\Services\Auth\UserKeyService;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureMasterKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            try {
                app(UserKeyService::class)->getMasterKey();
            } catch (Exception $e) {
                auth()->logout();
                return redirect()->route('login')->with('error', 'Session expired. Please log in again.');
            }
        }

        return $next($request);
    }
}
