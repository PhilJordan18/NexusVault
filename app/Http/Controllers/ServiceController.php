<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Mappers\ServiceMapper;
use App\Models\Service;
use App\Services\Vault\Contracts\ServiceServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ServiceController extends Controller
{
    public function __construct(private readonly ServiceServiceInterface $service, private readonly ServiceMapper $mapper) {}

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
                'id'         => $updated->id,
                'name'       => $updated->name,
                'url'        => $updated->url,
                'username'   => $updated->username,
                'password'   => $updated->password,
                'notes'      => $updated->notes,
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
        })->values();

        $userServices = Service::where('user_id', auth()->id())
            ->whereRaw('LOWER(name) like ?', ["%{$query}%"])
            ->select('name', 'url', 'favicon')
            ->distinct()
            ->limit(5)
            ->get()
            ->map(function ($service) {
                $domain = null;
                if ($service->url) {
                    $parsed = parse_url($service->url);
                    $domain = $parsed['host'] ?? null;
                    if ($domain && str_starts_with($domain, 'www.')) {
                        $domain = substr($domain, 4);
                    }
                }
                return [
                    'name'    => $service->name,
                    'url'     => $service->url,
                    'favicon' => $service->favicon ?? 'https://www.google.com/s2/favicons?domain=' . ($domain ?? 'example.com') . '&sz=64',
                    'domain'  => $domain,
                ];
            });

        if ($results->isEmpty() && $userServices->isEmpty()) {
            $domain = Str::slug($query) . '.com';
            $url = "https://www.{$domain}";
            $favicon = "https://www.google.com/s2/favicons?domain={$domain}&sz=64";
            return response()->json([[
                'name'    => ucfirst($query),
                'url'     => $url,
                'favicon' => $favicon,
                'domain'  => $domain,
            ]]);
        }

        $merged = $results->concat($userServices)->unique('name')->values();

        $merged = $merged->map(function ($item) {
            if (empty($item['favicon'])) {
                $domain = $item['domain'] ?? 'example.com';
                $item['favicon'] = "https://www.google.com/s2/favicons?domain={$domain}&sz=64";
            }
            return $item;
        });

        return response()->json($merged);
    }
}
