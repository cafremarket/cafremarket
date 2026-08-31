<?php

namespace App\Providers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Load helpers before routes boot (RouteServiceProvider runs before this provider's boot).
        foreach (glob(app_path().'/Helpers/*.php') as $filename) {
            require_once $filename;
        }
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // if (!$this->app->runningInConsole()) {
        //     if (is_incevio_package_loaded('zipcode')) {
        //         $zipCode = session('zipcode') ?? get_from_option_table('zipcode_default');
        //         Session::put('zipcode', $zipCode);
        //     }
        // }
    }
}
