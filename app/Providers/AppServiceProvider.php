<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

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
        $this->guardDestructiveDatabaseCommands();

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

    private function guardDestructiveDatabaseCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            $command = $event->command;
            $connection = (string) config('database.default');
            $database = (string) config("database.connections.{$connection}.database");

            if ($command === 'test' && $this->app->isProduction()) {
                throw new RuntimeException('Refusing to run tests in production.');
            }

            $destructiveCommands = [
                'db:wipe',
                'migrate:fresh',
                'migrate:refresh',
                'migrate:reset',
                'migrate:rollback',
            ];

            if (! in_array($command, $destructiveCommands, true)) {
                return;
            }

            $isExplicitlyAllowed = filter_var(env('ALLOW_DESTRUCTIVE_DB_COMMANDS', false), FILTER_VALIDATE_BOOL);
            $looksLikeTestDatabase = $connection === 'sqlite'
                || str_contains(strtolower($database), 'test')
                || $database === ':memory:';

            if (! $isExplicitlyAllowed && ! $looksLikeTestDatabase) {
                throw new RuntimeException(
                    'Refusing to run [' . $command . '] against database [' . $database . '] on connection [' . $connection . ']. '
                    . 'Use a test database, or set ALLOW_DESTRUCTIVE_DB_COMMANDS=true only after taking a backup.'
                );
            }
        });
    }
}
