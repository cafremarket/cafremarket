<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The path to the "admin dashboard" route for your application.
     *
     * @var string
     */
    public const DASHBOARD = 'admin/dashboard';

    /**
     * Merchant panel dashboard path.
     */
    public const MERCHANT_DASHBOARD = 'merchant/dashboard';

    /**
     * The path to the "admin login" route for your application.
     *
     * @var string
     */
    public const ADMIN_LOGIN = '/login';

    /**
     * Storefront home with login modal (no dedicated customer login page).
     *
     * @var string
     */
    public const CUSTOMER_LOGIN = '/?login=1';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            $this->mapApiRoutes();

            $this->mapWebRoutes();

            //
        });
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::get('.well-known/assetlinks.json', [
            \App\Http\Controllers\WellKnownController::class,
            'assetLinks',
        ]);
        Route::get('.well-known/apple-app-site-association', [
            \App\Http\Controllers\WellKnownController::class,
            'appleAppSiteAssociation',
        ]);

        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(base_path('routes/api.php'));
    }

    /**
     * Configure the rate limiters for the application.
     *
     * Mobile apps burst many parallel calls on home/dashboard. Auth middleware
     * runs after this limiter, so `$request->user()` is almost always null and
     * a 60/min IP bucket is shared across every guest + logged-in device on
     * the same NAT (typical on mobile carriers). That surfaces in the apps as
     * Laravel's "Too Many Attempts." 429.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            if ($this->isPaymentWebhookRequest($request)) {
                return Limit::none();
            }

            if ($this->isAuthAttemptRequest($request)) {
                $identity = strtolower((string) (
                    $request->input('email')
                    ?: $request->input('phone')
                    ?: $request->ip()
                ));

                return Limit::perMinute(20)->by('auth:'.$request->ip().'|'.$identity);
            }

            $token = $request->bearerToken()
                ?: $request->header('X-Auth-Token')
                ?: $request->input('api_token')
                ?: $request->query('api_token');

            if (is_string($token) && $token !== '') {
                return Limit::perMinute(600)->by('token:'.sha1($token));
            }

            return Limit::perMinute(300)->by('ip:'.$request->ip());
        });
    }

    protected function isPaymentWebhookRequest(Request $request): bool
    {
        return $request->is('api/emola/callback');
    }

    protected function isAuthAttemptRequest(Request $request): bool
    {
        if ($request->isMethod('GET')) {
            return false;
        }

        return $request->is(
            'api/auth/login',
            'api/auth/register',
            'api/auth/forgot',
            'api/auth/reset',
            'api/auth/social/*',
            'api/auth/customer/phone/verify',
            'api/vendor/auth/login',
            'api/vendor/auth/register',
            'api/vendor/auth/forgot',
            'api/vendor/auth/reset',
            'api/vendor/auth/user/phone/verify',
            'api/deliveryboy/login',
            'api/deliveryboy/forgot',
            'api/deliveryboy/reset'
        );
    }
}
