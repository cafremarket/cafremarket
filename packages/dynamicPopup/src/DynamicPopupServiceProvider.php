<?php

namespace Incevio\Package\DynamicPopup;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class DynamicPopupServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'DynamicPopup');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'DynamicPopup');
        //$this->loadMigrationsFrom(__DIR__.'../database/migrations');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'DynamicPopup');

        // Autoload helpers
        foreach (glob(__DIR__ . '/Helpers/*.php') as $filename) {
            require_once($filename);
        }
    }
}
