<?php

namespace Incevio\Package\LiveChat;

use App\Common\PackageConfig;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Incevio\Package\LiveChat\Models\ChatConversation;
use Incevio\Package\LiveChat\Policies\ChatConversationPolicy;

class LiveChatServiceProvider extends ServiceProvider
{
    use PackageConfig;

    protected $policies = [
        ChatConversation::class => ChatConversationPolicy::class,
    ];



    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'liveChat');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'liveChat');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'liveChat');


        //Register Policies for the package
        $this->registerPolicies();

        // Autoload helpers
        foreach (glob(__DIR__ . '/Helpers/*.php') as $filename) {
            require_once($filename);
        }

        // Ensure controllers added after last composer dump are loadable (classmap packages).
        $customerChat = __DIR__ . '/Http/Controllers/CustomerChatController.php';
        if (is_file($customerChat) && ! class_exists(\Incevio\Package\LiveChat\Http\Controllers\CustomerChatController::class, false)) {
            require_once $customerChat;
        }
    }
}
