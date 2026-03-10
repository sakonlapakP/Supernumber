<?php

namespace App\Providers;

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
        if (env('K_SERVICE')) {
            URL::forceScheme('https');

            $appUrl = (string) env('APP_URL', '');
            if ($appUrl !== '') {
                URL::forceRootUrl(preg_replace('/^http:/i', 'https:', $appUrl));
            }
        }
    }
}
