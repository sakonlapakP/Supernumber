<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Ga4AnalyticsService
{
    private const ANALYTICS_SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';

    public function measurementId(): string
    {
        return trim((string) config('services.ga4.measurement_id', ''));
    }

    public function propertyId(): string
    {
        return trim((string) config('services.ga4.property_id', ''));
    }

    public function dashboardCacheSeconds(): int
    {
        return max(0, (int) config('services.ga4.dashboard_cache_seconds', 900));
    }

    public function isClientTrackingConfigured(): bool
    {
        return $this->measurementId() !== '';
    }

    public function isReportingConfigured(): bool
    {
        return $this->propertyId() !== '' && $this->rawServiceAccountJsonBase64() !== '';
    }

    public function editableServiceAccountJson(): string
    {
        $base64 = $this->rawServiceAccountJsonBase64();

        if ($base64 === '') {
            return '';
        }

        $decoded = base64_decode($base64, true);

        if ($decoded === false || trim($decoded) === '') {
            return '';
        }

        $payload = json_decode($decoded, true);

        if (! is_array($payload)) {
            return '';
        }

        $pretty = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($pretty) ? $pretty : '';
    }

    public function serviceAccountEmail(): string
    {
        try {
            $credentials = $this->decodeStoredCredentials();
        } catch (\Throwable) {
            return '';
        }

        return trim((string) ($credentials['client_email'] ?? ''));
    }

    public function normalizeServiceAccountJson(?string $json): string
    {
        $json = trim((string) $json);

        if ($json === '') {
            return '';
        }

        $payload = json_decode($json, true);

        if (! is_array($payload)) {
            throw new RuntimeException('GA4 service account JSON ไม่ถูกต้อง');
        }

        foreach (['type', 'client_email', 'private_key', 'token_uri'] as $key) {
            if (trim((string) ($payload[$key] ?? '')) === '') {
                throw new RuntimeException(sprintf('GA4 service account JSON ต้องมีค่า %s', $key));
            }
        }

        if (trim((string) ($payload['type'] ?? '')) !== 'service_account') {
            throw new RuntimeException('GA4 service account JSON ต้องเป็น type = service_account');
        }

        $normalized = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (! is_string($normalized) || $normalized === '') {
            throw new RuntimeException('ไม่สามารถบันทึก GA4 service account JSON ได้');
        }

        return base64_encode($normalized);
    }

    /**
     * @return array{
     *     summary: array<string, float|int>,
     *     daily: array<int, array<string, float|int|string>>,
     *     sources: array<int, array<string, float|int|string>>,
     *     pages: array<int, array<string, float|int|string>>,
     *     events: array<int, array<string, float|int|string>>,
     *     devices: array<int, array<string, float|int|string>>,
     *     countries: array<int, array<string, float|int|string>>
     * }
     */
    public function fetchDashboard(int $days = 30): array
    {
        if (! $this->isReportingConfigured()) {
            throw new RuntimeException('ยังไม่ได้ตั้งค่า GA4 Property ID หรือ Service Account');
        }

        $days = in_array($days, [7, 30, 90], true) ? $days : 30;
        $cacheKey = sprintf(
            'ga4-dashboard:%s:%d',
            md5($this->propertyId() . '|' . $this->rawServiceAccountJsonBase64()),
            $days
        );

        $ttl = $this->dashboardCacheSeconds();

        if ($ttl <= 0) {
            return $this->buildDashboard($days);
        }

        return Cache::remember($cacheKey, now()->addSeconds($ttl), fn (): array => $this->buildDashboard($days));
    }

    /**
     * @return array{
     *     summary: array<string, float|int>,
     *     daily: array<int, array<string, float|int|string>>,
     *     sources: array<int, array<string, float|int|string>>,
     *     pages: array<int, array<string, float|int|string>>,
     *     events: array<int, array<string, float|int|string>>,
     *     devices: array<int, array<string, float|int|string>>,
     *     countries: array<int, array<string, float|int|string>>
     * }
     */
    private function buildDashboard(int $days): array
    {
        $dateRanges = [
            [
                'startDate' => ($days - 1) . 'daysAgo',
                'endDate' => 'today',
            ],
        ];

        $summaryResponse = $this->runReport([
            'dateRanges' => $dateRanges,
            'metrics' => [
                ['name' => 'activeUsers'],
                ['name' => 'newUsers'],
                ['name' => 'sessions'],
                ['name' => 'screenPageViews'],
                ['name' => 'engagementRate'],
                ['name' => 'averageSessionDuration'],
                ['name' => 'eventCount'],
            ],
        ]);

        $dailyResponse = $this->runReport([
            'dateRanges' => $dateRanges,
            'dimensions' => [
                ['name' => 'date'],
            ],
            'metrics' => [
                ['name' => 'activeUsers'],
                ['name' => 'sessions'],
                ['name' => 'screenPageViews'],
                ['name' => 'eventCount'],
            ],
            'orderBys' => [
                [
                    'dimension' => [
                        'dimensionName' => 'date',
                    ],
                ],
            ],
            'keepEmptyRows' => true,
        ]);

        $sourcesResponse = $this->runReport([
            'dateRanges' => $dateRanges,
            'dimensions' => [
                ['name' => 'sessionSourceMedium'],
            ],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'activeUsers'],
                ['name' => 'engagementRate'],
            ],
            'orderBys' => [
                [
                    'metric' => [
                        'metricName' => 'sessions',
                    ],
                    'desc' => true,
                ],
            ],
            'limit' => 10,
        ]);

        $pagesResponse = $this->runReport([
            'dateRanges' => $dateRanges,
            'dimensions' => [
                ['name' => 'pagePath'],
            ],
            'metrics' => [
                ['name' => 'screenPageViews'],
                ['name' => 'activeUsers'],
                ['name' => 'averageSessionDuration'],
            ],
            'orderBys' => [
                [
                    'metric' => [
                        'metricName' => 'screenPageViews',
                    ],
                    'desc' => true,
                ],
            ],
            'limit' => 10,
        ]);

        $eventsResponse = $this->runReport([
            'dateRanges' => $dateRanges,
            'dimensions' => [
                ['name' => 'eventName'],
            ],
            'metrics' => [
                ['name' => 'eventCount'],
                ['name' => 'totalUsers'],
            ],
            'orderBys' => [
                [
                    'metric' => [
                        'metricName' => 'eventCount',
                    ],
                    'desc' => true,
                ],
            ],
            'limit' => 10,
        ]);

        $devicesResponse = $this->runReport([
            'dateRanges' => $dateRanges,
            'dimensions' => [
                ['name' => 'deviceCategory'],
            ],
            'metrics' => [
                ['name' => 'activeUsers'],
                ['name' => 'sessions'],
            ],
            'orderBys' => [
                [
                    'metric' => [
                        'metricName' => 'activeUsers',
                    ],
                    'desc' => true,
                ],
            ],
            'limit' => 10,
        ]);

        $countriesResponse = $this->runReport([
            'dateRanges' => $dateRanges,
            'dimensions' => [
                ['name' => 'country'],
            ],
            'metrics' => [
                ['name' => 'activeUsers'],
                ['name' => 'sessions'],
            ],
            'orderBys' => [
                [
                    'metric' => [
                        'metricName' => 'activeUsers',
                    ],
                    'desc' => true,
                ],
            ],
            'limit' => 10,
        ]);

        return [
            'summary' => $this->firstRow($summaryResponse),
            'daily' => $this->rows($dailyResponse),
            'sources' => $this->rows($sourcesResponse),
            'pages' => $this->rows($pagesResponse),
            'events' => $this->rows($eventsResponse),
            'devices' => $this->rows($devicesResponse),
            'countries' => $this->rows($countriesResponse),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function runReport(array $payload): array
    {
        $propertyId = $this->propertyId();

        if ($propertyId === '') {
            throw new RuntimeException('ยังไม่ได้ตั้งค่า GA4 Property ID');
        }

        $response = Http::timeout(30)
            ->acceptJson()
            ->withToken($this->accessToken())
            ->post(sprintf('https://analyticsdata.googleapis.com/v1beta/properties/%s:runReport', $propertyId), $payload);

        if ($response->failed()) {
            throw new RuntimeException($this->resolveGoogleErrorMessage($response));
        }

        return $response->json() ?? [];
    }

    private function accessToken(): string
    {
        $credentials = $this->decodeStoredCredentials();
        $cacheKey = 'ga4-access-token:' . md5(
            trim((string) ($credentials['client_email'] ?? ''))
            . '|' .
            trim((string) ($credentials['private_key'] ?? ''))
        );

        return Cache::remember($cacheKey, now()->addMinutes(55), function () use ($credentials): string {
            $tokenUri = trim((string) ($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token'));
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(20)
                ->post($tokenUri, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $this->buildSignedJwt($credentials),
                ]);

            if ($response->failed()) {
                throw new RuntimeException($this->resolveGoogleErrorMessage($response));
            }

            $token = trim((string) data_get($response->json(), 'access_token', ''));

            if ($token === '') {
                throw new RuntimeException('Google OAuth token response ไม่มี access_token');
            }

            return $token;
        });
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function buildSignedJwt(array $credentials): string
    {
        $tokenUri = trim((string) ($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token'));
        $clientEmail = trim((string) ($credentials['client_email'] ?? ''));
        $privateKey = (string) ($credentials['private_key'] ?? '');
        $now = time();

        if ($clientEmail === '' || $privateKey === '' || $tokenUri === '') {
            throw new RuntimeException('GA4 service account credentials ไม่ครบ');
        }

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_UNESCAPED_SLASHES) ?: '{}');

        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => self::ANALYTICS_SCOPE,
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_UNESCAPED_SLASHES) ?: '{}');

        $unsignedToken = $header . '.' . $payload;
        $signature = '';

        $success = openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (! $success) {
            throw new RuntimeException('ไม่สามารถ sign GA4 service account JWT ได้');
        }

        return $unsignedToken . '.' . $this->base64UrlEncode($signature);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeStoredCredentials(): array
    {
        $base64 = $this->rawServiceAccountJsonBase64();

        if ($base64 === '') {
            throw new RuntimeException('ยังไม่ได้ตั้งค่า GA4 Service Account');
        }

        $decoded = base64_decode($base64, true);

        if ($decoded === false || trim($decoded) === '') {
            throw new RuntimeException('ไม่สามารถอ่าน GA4 Service Account จากค่าที่บันทึกไว้ได้');
        }

        $payload = json_decode($decoded, true);

        if (! is_array($payload)) {
            throw new RuntimeException('GA4 Service Account JSON ไม่ถูกต้อง');
        }

        return $payload;
    }

    private function rawServiceAccountJsonBase64(): string
    {
        return trim((string) config('services.ga4.service_account_json_base64', ''));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, float|int|string>
     */
    private function firstRow(array $response): array
    {
        $rows = $this->rows($response);

        return $rows[0] ?? [];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, float|int|string>>
     */
    private function rows(array $response): array
    {
        $dimensionHeaders = array_map(
            static fn (array $header): string => (string) ($header['name'] ?? ''),
            array_values(array_filter($response['dimensionHeaders'] ?? [], 'is_array'))
        );
        $metricHeaders = array_map(
            static fn (array $header): string => (string) ($header['name'] ?? ''),
            array_values(array_filter($response['metricHeaders'] ?? [], 'is_array'))
        );

        return array_values(array_map(function (array $row) use ($dimensionHeaders, $metricHeaders): array {
            $parsed = [];
            $dimensionValues = array_values(array_filter($row['dimensionValues'] ?? [], 'is_array'));
            $metricValues = array_values(array_filter($row['metricValues'] ?? [], 'is_array'));

            foreach ($dimensionHeaders as $index => $header) {
                $parsed[$header] = (string) ($dimensionValues[$index]['value'] ?? '');
            }

            foreach ($metricHeaders as $index => $header) {
                $parsed[$header] = $this->castMetricValue((string) ($metricValues[$index]['value'] ?? '0'));
            }

            return $parsed;
        }, array_values(array_filter($response['rows'] ?? [], 'is_array'))));
    }

    /**
     * @return float|int
     */
    private function castMetricValue(string $value)
    {
        if ($value === '') {
            return 0;
        }

        if (preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return (float) $value;
    }

    private function resolveGoogleErrorMessage(Response $response): string
    {
        $message = trim((string) data_get($response->json(), 'error.message', ''));

        if ($message !== '') {
            return $message;
        }

        return 'ไม่สามารถเชื่อมต่อ Google Analytics Data API ได้';
    }
}
