<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Str;

final readonly class PasswordService
{
    public function __construct(private Http $http) {}

    public function calculateEntropy(string $password) : array {
        $length = strlen($password);
        if ($length === 0) {
            return ['entropy' => 0, 'strength' => 'very_weak', 'label' => 'Very weak'];
        }

        $charsetSize = 0;
        if (preg_match('/[a-z]/', $password)) $charsetSize +=26;
        if (preg_match('/[A-Z]/', $password)) $charsetSize +=26;
        if (preg_match('/[0-9]/', $password)) $charsetSize +=26;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $charsetSize +=32;
        $entropy = $length * log($charsetSize, 2);
        return [
            'entropy' => round($entropy, 2),
            'strength' => $this->getStrength($entropy),
            'label' => $this->getStrengthLabel($entropy),
        ];
    }

    public function generate(int $length = 16, bool $upper, bool $lower, bool $numbers, bool $symbols) : string {
        $chars = '';
        if ($lower) $chars .= 'abcdefghijklmnopqrstuvwxyz';
        if ($upper) $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if ($numbers) $chars .= '0123456789';
        if ($symbols) $chars .= '!@#$%^&*()-_=+[]{}|;:,.<>?';

        return Str::password($length, $upper, $lower, $numbers, $symbols, $chars);
    }

    public function analyze(string $password, int $userId): array
    {
        $entropy = $this->calculateEntropy($password);

        $compromised = $this->isPwned($password);
        $reused = $this->isReused($password, $userId);

        return [
            'entropy'     => $entropy['entropy'],
            'strength'    => $entropy['strength'],
            'compromised' => $compromised,
            'reused'      => $reused,
            'label'       => $entropy['label'],
        ];
    }

    public function isPwned(string $password): bool
    {
        $sha1 = strtoupper(sha1($password));
        $prefix = substr($sha1, 0, 5);

        try {
            $response = $this->http->get("https://api.pwnedpasswords.com/range/{$prefix}")
                ->throw()
                ->body();
            return Str::contains($response, substr($sha1, 5));
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function isReused(string $password, int $userId): bool
    {
        $services = Service::where('user_id', $userId)->get();
        foreach ($services as $service) {
            if ($service->password === $password) {
                return true;
            }
        }
        return false;
    }

    private function getStrength(float $entropy) : string {
        return match (true) {
            $entropy < 40 => 'very_weak',
            $entropy < 60 => 'weak',
            $entropy < 80 => 'medium',
            $entropy < 100 => 'strong',
            default => 'very_strong',
        };
    }

    private function getStrengthLabel(float $entropy) : string {
        return match (true) {
            $entropy < 40 => 'Very weak',
            $entropy < 60 => 'Weak',
            $entropy < 80 => 'Medium',
            $entropy < 100 => 'Strong',
            default => 'Very strong',
        };
    }
}
