<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('tarot-ai', function (Request $request): Limit {
            $ip = (string) ($request->ip() ?? 'unknown');
            $userAgent = substr((string) $request->userAgent(), 0, 120);

            return Limit::perMinute(12)->by($ip . '|' . $userAgent);
        });

        RateLimiter::for('contact-messages', function (Request $request): array {
            $ip = (string) ($request->ip() ?? 'unknown');
            $userAgent = substr((string) $request->userAgent(), 0, 120);
            $signature = $ip . '|' . $userAgent;

            $tooManyAttemptsResponse = static function (Request $request, array $headers = []) {
                return redirect()
                    ->route('contact')
                    ->withInput($request->except('_token', 'cf-turnstile-response', 'website'))
                    ->withErrors(['contact' => 'ส่งข้อความถี่เกินไป กรุณารอสักครู่แล้วลองใหม่อีกครั้ง']);
            };

            return [
                Limit::perMinute(5)->by($signature)->response($tooManyAttemptsResponse),
                Limit::perHour(20)->by($signature)->response($tooManyAttemptsResponse),
            ];
        });

        if (env('K_SERVICE')) {
            URL::forceScheme('https');

            $appUrl = (string) env('APP_URL', '');
            if ($appUrl !== '') {
                URL::forceRootUrl(preg_replace('/^http:/i', 'https:', $appUrl));
            }
        }
    }
}
