<?php

use App\Services\Vault\FaviconService;

test('it resolves clean favicon urls for valid domains and urls', function () {
    $service = new FaviconService;

    expect($service->normalizeDomain('https://www.GitHub.com/login'))->toBe('github.com')
        ->and($service->domainFromUrl('https://sub.example.com/path'))->toBe('sub.example.com')
        ->and($service->urlFor(null, 'github.com'))->toBe('https://www.github.com')
        ->and($service->iconFor(null, 'github.com'))->toBe('https://www.google.com/s2/favicons?domain=github.com&sz=128');
});

test('it falls back to the NexusVault monogram for invalid domains', function () {
    $service = new FaviconService;

    expect($service->normalizeDomain('localhost'))->toBeNull()
        ->and($service->normalizeDomain('127.0.0.1'))->toBeNull()
        ->and($service->normalizeDomain('not a valid domain'))->toBeNull()
        ->and($service->iconFor('not a url'))->toBe(FaviconService::DEFAULT_ICON_PATH);
});
