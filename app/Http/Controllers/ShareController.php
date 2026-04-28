<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShareRequest;
use App\Models\Service;
use App\Models\Share;
use App\Services\Vault\Contracts\ShareServiceInterface;
use Illuminate\Http\RedirectResponse;

final class ShareController extends Controller
{
    public function __construct(private readonly ShareServiceInterface $shareService) {}

    public function store(ShareRequest $request) {
        $request->validated();
        $service = Service::findOrFail($request->service_id);
        if ($service->user_id !== auth()->id()) abort(403);

        $this->shareService->share($service, $request->email);

        return back()->with('success', 'Share sent successfully!');
    }

    public function accept(Share $share): RedirectResponse {
        $this->shareService->accept($share);
        return redirect()->route('dashboard')->with('success', 'Service added to your vault with success!');
    }

    public function reject(Share $share): RedirectResponse {
        $this->shareService->reject($share);
        return redirect()->route('dashboard')->with('info', 'Sharing rejected!');
    }
}
