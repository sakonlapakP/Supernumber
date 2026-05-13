<?php

namespace App\Services;

use App\Models\BillingCustomer;
use App\Models\CustomerSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CustomerSubmissionRecorder
{
    /**
     * @param array<string, mixed> $payload
     */
    public function record(Request $request, string $formType, array $payload): CustomerSubmission
    {
        $name = $this->cleanString($payload['name'] ?? null)
            ?? (trim(implode(' ', array_filter([
                $this->cleanString($payload['first_name'] ?? null),
                $this->cleanString($payload['last_name'] ?? null),
            ]))) ?: null);

        $phone = $this->normalizePhone(
            $payload['phone']
                ?? $payload['main_phone']
                ?? $payload['current_phone']
                ?? null
        );
        $email = $this->cleanString($payload['email'] ?? null);
        $customer = $this->resolveCustomer($name, $phone, $email);

        // Keep the raw form context for admins while stripping transport, bot-trap, and CAPTCHA fields.
        return CustomerSubmission::query()->create([
            'customer_id' => $customer?->id,
            'form_type' => $formType,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'payload' => Arr::except($payload, ['_token', 'cf-turnstile-response', 'website']),
            'consent_dev' => $this->booleanConsent($request->input('consent_dev')),
            'consent_marketing' => $this->booleanConsent($request->input('consent_marketing')),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 65535),
            'submitted_at' => now(),
        ]);
    }

    private function resolveCustomer(?string $name, ?string $phone, ?string $email): ?BillingCustomer
    {
        if ($phone === null && $email === null && $name === null) {
            return null;
        }

        $customer = null;

        if ($phone !== null || $email !== null) {
            // Phone/email identify repeat customers across contact, estimate, and evaluation forms.
            $customer = BillingCustomer::query()
                ->where(function ($query) use ($phone, $email) {
                    if ($phone !== null) {
                        $query->orWhere('phone', $phone);
                    }

                    if ($email !== null) {
                        $query->orWhere('email', $email);
                    }
                })
                ->first();
        }

        if ($customer !== null) {
            return $customer;
        }

        $nameParts = $name !== null ? preg_split('/\s+/', $name) ?: [] : [];
        $firstName = $nameParts !== [] ? array_shift($nameParts) : null;
        $lastName = $nameParts !== [] ? implode(' ', $nameParts) : null;

        return BillingCustomer::query()->create([
            'first_name' => $firstName ?: null,
            'last_name' => $lastName ?: null,
            'phone' => $phone,
            'email' => $email,
            'is_active' => true,
        ]);
    }

    private function cleanString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function normalizePhone(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        return $digits !== '' ? $digits : null;
    }

    private function booleanConsent(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
