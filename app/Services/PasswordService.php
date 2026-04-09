<?php

namespace App\Services;

use Illuminate\Support\Str;

final readonly class PasswordService
{
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
