<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShareRequest;
use App\Mappers\ShareMapper;
use App\Models\Service;
use App\Models\Share;
use App\Services\Vault\Contracts\ShareServiceInterface;
use Illuminate\Http\RedirectResponse;

final class ShareController extends Controller
{
    public function __construct(private readonly ShareServiceInterface $shareService) {}

    public function store(ShareRequest $request) {

        $sharedData = ShareMapper::fromRequest($request->validated());
        $this->shareService->share($sharedData);
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
