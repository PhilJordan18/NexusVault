<?php

namespace App\Services\Vault;

use Illuminate\Support\Str;

final class FaviconService
{
    public const DEFAULT_ICON_PATH = '/logo/LogoMonogramme.svg';

    public function iconFor(?string $url = null, ?string $domain = null, int $size = 128): string
    {
        $normalizedDomain = $this->normalizeDomain($domain) ?? $this->domainFromUrl($url);

        if (! $normalizedDomain) {
            return self::DEFAULT_ICON_PATH;
        }

        $safeSize = min(max($size, 16), 256);

        return 'https://www.google.com/s2/favicons?domain='.rawurlencode($normalizedDomain).'&sz='.$safeSize;
    }

    public function urlFor(?string $url = null, ?string $domain = null): ?string
    {
        if ($url) {
            $urlWithScheme = $this->ensureUrlScheme($url);

            if ($this->domainFromUrl($urlWithScheme)) {
                return $urlWithScheme;
            }
        }

        $normalizedDomain = $this->normalizeDomain($domain);

        return $normalizedDomain ? "https://www.{$normalizedDomain}" : null;
    }

    public function domainFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $host = parse_url($this->ensureUrlScheme($url), PHP_URL_HOST);

        return $this->normalizeDomain($host);
    }

    public function domainFromName(string $name): ?string
    {
        $slug = Str::slug($name);

        return $slug ? "{$slug}.com" : null;
    }

    public function normalizeDomain(?string $domain): ?string
    {
        if (! $domain) {
            return null;
        }

        $candidate = trim(mb_strtolower($domain));
        $candidate = preg_replace('/^https?:\/\//', '', $candidate);
        $candidate = explode('/', $candidate)[0] ?? '';
        $candidate = explode(':', $candidate)[0] ?? '';
        $candidate = preg_replace('/^www\./', '', $candidate);
        $candidate = trim($candidate, ". \t\n\r\0\x0B");

        if (! $candidate || strlen($candidate) > 253) {
            return null;
        }

        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return null;
        }

        if (! preg_match('/\A[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+\z/', $candidate)) {
            return null;
        }

        return $candidate;
    }

    private function ensureUrlScheme(string $url): string
    {
        $trimmed = trim($url);

        return str_contains($trimmed, '://') ? $trimmed : "https://{$trimmed}";
    }
}
