<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Mappers\ServiceMapper;
use App\Models\Service;
use App\Models\Share;
use App\Services\Vault\Contracts\ServiceServiceInterface;
use App\Services\Vault\FaviconService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ServiceController extends Controller
{
    public function __construct(
        private readonly ServiceServiceInterface $service,
        private readonly ServiceMapper $mapper,
        private readonly FaviconService $faviconService
    ) {}

    public function index()
    {
        $grouped = $this->service->getGroupedByName(auth()->id());

        return view('dashboard.index', compact('grouped'));
    }

    public function store(CreateServiceRequest $request): RedirectResponse
    {
        $data = $this->mapper->fromCreateRequest($request);
        $this->service->create($data);

        return redirect()->route('dashboard')->with('success', 'Service added successfully!');
    }

    public function show(string $name)
    {
        $accounts = $this->service->getAccountsByName($name, auth()->id());
        $sharesByService = Share::with('toUser')
            ->where('from_user_id', auth()->id())
            ->whereIn('service_id', $accounts->pluck('id'))
            ->where('rejected', false)
            ->whereNull('revoked_at')
            ->latest()
            ->get()
            ->groupBy('service_id')
            ->map(fn ($shares) => $shares->map(fn (Share $share) => [
                'id' => $share->id,
                'name' => $share->toUser?->name,
                'email' => $share->toUser?->email,
                'status' => $share->accepted_at ? 'Accepted' : 'Pending',
                'shared_at' => $share->shared_at?->diffForHumans(),
            ])->values());

        $accounts->each(function (Service $account) use ($sharesByService): void {
            $account->setAttribute('shared_with', $sharesByService->get($account->id, collect())->values());
        });

        return view('dashboard.service', compact('accounts', 'name'));
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        if ($service->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $this->mapper->fromUpdateRequest($request);
        $updated = $this->service->update($service, $data);

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $updated->id,
                'name' => $updated->name,
                'url' => $updated->url,
                'username' => $updated->username,
                'password' => $updated->password,
                'notes' => $updated->notes,
                'updated_at' => $updated->updated_at->diffForHumans(),
            ]);
        }

        return back()->with('success', 'Service updated successfully!');
    }

    public function destroy(Service $service): JsonResponse|RedirectResponse
    {
        if ($service->user_id !== auth()->id()) {
            abort(403);
        }

        $this->service->delete($service);

        return request()->expectsJson()
            ? response()->json(['message' => 'Service deleted'])
            : redirect()->route('dashboard')->with('success', 'Service deleted');
    }

    public function suggest(Request $request)
    {
        $query = mb_strtolower(trim($request->get('name', '')));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $predefined = collect(config('services-suggestions', []));

        $results = $predefined->filter(function ($item) use ($query) {
            return str_contains(mb_strtolower($item['name']), $query);
        })->map(function (array $item): array {
            $domain = $this->faviconService->normalizeDomain($item['domain'] ?? null)
                ?? $this->faviconService->domainFromUrl($item['url'] ?? null);

            $url = $this->faviconService->urlFor($item['url'] ?? null, $domain);

            return [
                'name' => $item['name'],
                'url' => $url,
                'favicon' => $this->faviconService->iconFor($url, $domain),
                'domain' => $domain,
            ];
        })->values();

        $userServices = Service::where('user_id', auth()->id())
            ->whereRaw('LOWER(name) like ?', ["%{$query}%"])
            ->select('name', 'url', 'favicon')
            ->distinct()
            ->limit(5)
            ->get()
            ->map(function (Service $service) {
                $domain = $this->faviconService->domainFromUrl($service->url);

                return [
                    'name' => $service->name,
                    'url' => $service->url,
                    'favicon' => $service->favicon ?: $this->faviconService->iconFor($service->url, $domain),
                    'domain' => $domain,
                ];
            });

        if ($results->isEmpty() && $userServices->isEmpty()) {
            $domain = $this->faviconService->domainFromName($query);
            $url = $this->faviconService->urlFor(null, $domain);

            return response()->json([[
                'name' => ucfirst($query),
                'url' => $url,
                'favicon' => $this->faviconService->iconFor($url, $domain),
                'domain' => $domain,
            ]]);
        }

        $merged = $results->concat($userServices)->unique('name')->values();

        $merged = $merged->map(function ($item) {
            if (empty($item['favicon'])) {
                $item['favicon'] = $this->faviconService->iconFor($item['url'] ?? null, $item['domain'] ?? null);
            }

            return $item;
        });

        return response()->json($merged);
    }
}
