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
            $user = $request->user();

            if ($user?->requiresClientVaultSetup()) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => __('Set up your encrypted vault to continue.')], 428);
                }

                $request->session()->put('url.intended', $request->fullUrl());

                return redirect()->route('vault.setup')->with('error', __('Set up your encrypted vault to continue.'));
            }

            if ($user?->usesClientSideVault()) {
                if ($request->session()->has('vault_unlocked_at')) {
                    return $next($request);
                }

                if ($request->expectsJson()) {
                    return response()->json(['message' => __('Vault is locked.')], 423);
                }

                $request->session()->put('url.intended', $request->fullUrl());

                return redirect()->route('vault.unlock')->with('error', __('Unlock your vault to continue.'));
            }

            try {
                app(UserKeyService::class)->getMasterKey();
            } catch (Exception $e) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => __('Vault is locked.')], 423);
                }

                $request->session()->put('url.intended', $request->fullUrl());

                return redirect()->route('vault.unlock')->with('error', __('Unlock your vault to continue.'));
            }
        }

        return $next($request);
    }
}
