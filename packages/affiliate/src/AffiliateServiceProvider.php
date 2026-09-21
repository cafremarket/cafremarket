<?php

namespace Incevio\Package\Affiliate;

use App\Common\PackageConfig;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AffiliateServiceProvider extends ServiceProvider
{
    use PackageConfig;

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Affiliate panel (login/dashboard) is web-only.
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Customer/vendor app attribution + product affiliate fields (no affiliate panel API).
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'affiliate');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->ensureAffiliateClassesLoaded();

        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'affiliate');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'affiliate');

        // Autoload helpers
        foreach (glob(__DIR__ . '/Helpers/*.php') as $filename) {
            require_once($filename);
        }
    }

    /**
     * Load new affiliate classes if Composer classmap is stale.
     */
    protected function ensureAffiliateClassesLoaded(): void
    {
        $classes = [
            \Incevio\Package\Affiliate\Services\AffiliateAttributionService::class => __DIR__.'/Services/AffiliateAttributionService.php',
            \Incevio\Package\Affiliate\Services\AffiliateCommissionService::class => __DIR__.'/Services/AffiliateCommissionService.php',
            \Incevio\Package\Affiliate\Http\Controllers\Api\AttributionController::class => __DIR__.'/Http/Controllers/Api/AttributionController.php',
        ];

        foreach ($classes as $class => $path) {
            if (! class_exists($class, false) && is_file($path)) {
                require_once $path;
            }
        }
    }
}
