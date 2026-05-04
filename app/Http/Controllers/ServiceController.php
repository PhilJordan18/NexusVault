<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use App\Services\Vault\Contracts\ServiceServiceInterface;
use Illuminate\Http\RedirectResponse;

final class ServiceController extends Controller
{
    public function __construct(private readonly ServiceServiceInterface $service) {}

    public function index()
    {
        $grouped = $this->service->getGroupedByName(auth()->id());
        return view('dashboard.index', compact('grouped'));
    }

    public function store(CreateServiceRequest $request): RedirectResponse {
        $this->service->create($request->validated());
        return redirect()->route('dashboard')->with('success', 'Service added with success!');
    }

    public function show(string $name)
    {
        $accounts = Service::where('user_id', auth()->id())->where('name', $name)->orderBy('updated_at', 'desc')->get();
        return view('dashboard.service', compact('accounts', 'name'));
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        if ($service->user_id !== auth()->id()) {
            abort(403);
        }

        $updatedService = $this->service->update($service, $request->validated());

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'id'         => $updatedService->id,
                'name'       => $updatedService->name,
                'url'        => $updatedService->url,
                'username'   => $updatedService->username,
                'password'   => $updatedService->password,
                'notes'      => $updatedService->notes,
                'updated_at' => $updatedService->updated_at->diffForHumans(),
            ]);
        }

        return back()->with('success', 'Service updated with success!');
    }

    public function destroy(Service $service): RedirectResponse
    {
        if ($service->user_id !== auth()->id()) abort(403);
        $this->service->delete($service);
        return back()->with('success', 'Service supprimé');
    }
}
