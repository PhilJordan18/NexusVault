<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(): View {
        $services = Service::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        $stats = [
            'compromised' => $services->where('compromised', true)->count(),
            'reused'      => $services->where('reused', true)->count(),
            'weak'        => $services->whereIn('strength', ['very_weak', 'weak'])->count(),
            'secure' => $services->whereIn('strength', ['strong', 'very_strong'])->count(),
        ];

        $grouped = $services->groupBy('name')->map(function ($items, $name) {
            return (object) [
                'name'          => $name,
                'favicon'       => $items->firstWhere('favicon', '!=', null)->favicon ?? null,
                'url'           => $items->firstWhere('url', '!=', null)->url ?? null,
                'account_count' => $items->count(),
                'last_modified' => $items->max('updated_at'),
            ];
        })->values();

        return view('dashboard.index', compact('grouped', 'stats'));
    }

    public function show(string $serviceName)
    {
        $accounts = Service::where('user_id', auth()->id())->where('name', $serviceName)->orderBy('updated_at', 'desc')->get();
        return view('dashboard.service', compact('accounts', 'serviceName'));
    }
}
