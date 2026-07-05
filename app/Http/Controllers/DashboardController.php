<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(): View
    {
        $services = Service::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        $stats = [
            'compromised' => $services->where('type', Service::TYPE_LOGIN)->where('compromised', true)->count(),
            'reused' => $services->where('type', Service::TYPE_LOGIN)->where('reused', true)->count(),
            'weak' => $services->where('type', Service::TYPE_LOGIN)->whereIn('strength', ['very_weak', 'weak'])->count(),
            'cards' => $services->where('type', Service::TYPE_PAYMENT_CARD)->count(),
        ];

        $grouped = $services->groupBy(fn (Service $service) => $service->type.'|'.$service->name)->map(function ($items) {
            return (object) [
                'name' => $items->first()->name,
                'type' => $items->first()->type,
                'favicon' => $items->firstWhere('favicon', '!=', null)->favicon ?? null,
                'url' => $items->firstWhere('url', '!=', null)->url ?? null,
                'account_count' => $items->count(),
                'last_modified' => $items->max('updated_at'),
            ];
        })->values();

        return view('dashboard.index', compact('grouped', 'stats'));
    }

    public function show(string $serviceName): View|RedirectResponse
    {
        $accounts = Service::where('user_id', auth()->id())->where('name', $serviceName)->orderBy('updated_at', 'desc')->get();

        if ($accounts->isEmpty()) {
            return redirect()
                ->route('dashboard')
                ->with('error', __('This service is no longer available.'));
        }

        $name = $serviceName;

        return view('dashboard.service', compact('accounts', 'name'));
    }
}
