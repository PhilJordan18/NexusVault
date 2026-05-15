<?php

namespace App\Http\Controllers;

use App\Services\PasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PasswordController extends Controller
{
    public function __construct(private readonly PasswordService $passwordService) {}

    public function generate(Request $request) {
        $password = $this->passwordService->generate(
            length: (int) $request->get('length', 16),
            upper: $request->boolean('upper', true),
            lower: $request->boolean('lower', true),
            numbers: $request->boolean('numbers', true),
            symbols: $request->boolean('symbols', true)
        );

        return response()->json(['password' => $password]);
    }

    public function entropy(Request $request): JsonResponse
    {
        $password = $request->get('password', '');
        $userId = auth()->id();

        $result = $this->passwordService->analyze($password, $userId ?? 0);
        return response()->json($result);
    }
}
