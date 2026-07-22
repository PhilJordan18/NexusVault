<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Share;
use App\Models\User;
use Illuminate\Contracts\View\View;

final class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'users' => User::count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'oauth_users' => User::where('is_oauth', true)->count(),
            'mfa_users' => User::where('mfa_enabled', true)->count(),
            'client_vault_users' => User::whereNotNull('vault_key_envelope')->count(),
            'legacy_vault_users' => User::whereNotNull('encrypted_master_key')->count(),
            'items' => Service::count(),
            'client_encrypted_items' => Service::where('client_encrypted', true)->count(),
            'active_shares' => Share::whereNotNull('accepted_at')->whereNull('revoked_at')->count(),
            'pending_shares' => Share::whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('rejected', false)
                ->count(),
        ];

        $users = User::query()
            ->withCount('services')
            ->latest()
            ->paginate(25);

        return view('admin.index', compact('stats', 'users'));
    }
}
