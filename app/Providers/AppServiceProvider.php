<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('logs'),
            storage_path('app/public'),
            storage_path('app/private'),
        ] as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        }
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        RateLimiter::for('site-applications', function (Request $request) {
            return Limit::perHour(6)
                ->by((string) $request->ip())
                ->response(function () {
                    return redirect()
                        ->route('apply.create')
                        ->withErrors(['form' => __('app.apply.throttle', [], 'en')]);
                });
        });
    }
}
