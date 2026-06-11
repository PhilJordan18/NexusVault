<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShareRequest;
use App\Mappers\ShareMapper;
use App\Models\Share;
use App\Services\Vault\Contracts\ShareServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class ShareController extends Controller
{
    public function __construct(private readonly ShareServiceInterface $shareService) {}

    public function store(ShareRequest $request): RedirectResponse
    {

        $sharedData = ShareMapper::fromRequest($request->validated());
        $this->shareService->share($sharedData);

        return back()->with('success', __('Share sent successfully!'));
    }

    public function accept(Share $share): RedirectResponse
    {
        $this->shareService->accept($share);

        return redirect()->route('dashboard')->with('success', __('Service added to your vault with success!'));
    }

    public function reject(Share $share): RedirectResponse
    {
        $this->shareService->reject($share);

        return redirect()->route('dashboard')->with('info', __('Sharing rejected!'));
    }

    public function revoke(Share $share): JsonResponse|RedirectResponse
    {
        $this->shareService->revoke($share);

        return request()->expectsJson()
            ? response()->json(['message' => __('Shared access revoked.')])
            : back()->with('success', __('Shared access revoked.'));
    }
}
