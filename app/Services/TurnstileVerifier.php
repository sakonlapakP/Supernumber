<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TurnstileVerifier
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function isEnabled(): bool
    {
        return $this->siteKey() !== '' && $this->secretKey() !== '';
    }

    public function siteKey(): string
    {
        return trim((string) config('services.turnstile.site_key', ''));
    }

    public function verify(string $token, ?string $ipAddress = null): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        $token = trim($token);

        if ($token === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(5)
                ->post(self::VERIFY_URL, array_filter([
                    'secret' => $this->secretKey(),
                    'response' => $token,
                    'remoteip' => $ipAddress ?: null,
                ], static fn ($value): bool => $value !== null && $value !== ''));
        } catch (\Throwable) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        return (bool) data_get($response->json(), 'success', false);
    }

    private function secretKey(): string
    {
        return trim((string) config('services.turnstile.secret_key', ''));
    }
}
