<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower((string) $request->input('email')).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey)->response(function (Request $request, array $headers) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Demasiados intentos de inicio de sesión. Por favor, intenta nuevamente en 1 minuto.',
                ], 429, $headers);
            });
        });
    }
}
