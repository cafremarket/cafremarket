<?php

namespace App\Providers;

use App\Contracts\PaymentServiceContract;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

// use Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Always use current app storage path for sessions (fixes cached config from another environment)
        $sessionPath = storage_path('framework/sessions');
        config(['session.files' => $sessionPath]);

        $this->ensureStorageFrameworkDirectoriesExist();

        if ($this->app->environment('production') && filter_var(env('EMOLA_FAKE', false), FILTER_VALIDATE_BOOLEAN)) {
            \Illuminate\Support\Facades\Log::warning(
                'EMOLA_FAKE=true in .env on production is ignored — set EMOLA_FAKE=false and run php artisan config:clear so real USSD pushes are sent.'
            );
        }

        if (
            isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
        ) {
            URL::forceScheme('https');
        }

        // Keep Debugbar off outside local — it adds heavy per-request overhead.
        if (! $this->app->environment('local') && class_exists(\Barryvdh\Debugbar\Facade::class)) {
            \Barryvdh\Debugbar\Facade::disable();
        }

        // Disable lazy loading to avoid n+1 problem (except on production server)
        // Model::preventLazyLoading(!$this->app->isProduction());

        Blade::withoutDoubleEncoding();
        Paginator::useBootstrapThree();
        // Artisan::call('dump-autoload');

        // Add Google recaptcha validation rule
        Validator::extend('recaptcha', 'App\\Helpers\\ReCaptcha@validate');

        // Production reCAPTCHA keys are domain-restricted; skip on localhost unless explicitly enabled.
        if (! $this->app->runningInConsole() && config('services.recaptcha.key')) {
            $host = request()->getHost();
            $localHosts = ['localhost', '127.0.0.1', '[::1]'];
            $allowOnLocalhost = filter_var(env('RECAPTCHA_ON_LOCALHOST', false), FILTER_VALIDATE_BOOLEAN);

            if (in_array($host, $localHosts, true) && ! $allowOnLocalhost) {
                config([
                    'services.recaptcha.key' => null,
                    'services.recaptcha.secret' => null,
                ]);
            }
        }

        // Disable encryption for gdpr cookie
        $this->app->resolving(EncryptCookies::class, function (EncryptCookies $encryptCookies) {
            $encryptCookies->disableFor(config('gdpr.cookie.name'));
        });

        // Add pagination on collections
        if (! Collection::hasMacro('paginate')) {
            Collection::macro('paginate', function ($perPage = 15, $page = null, $options = []) {
                $q = url()->full();
                // Remove unwanted page parameter from the url if exist
                if (Request::has('page')) {
                    $q = remove_url_parameter($q, 'page');
                }

                $page = $page ?? Paginator::resolveCurrentPage() ?? 1;

                $paginator = new LengthAwarePaginator($this->forPage($page, $perPage), $this->count(), $perPage, $page, $options);

                return $paginator->withPath($q);
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Need for cashier
        // Cashier::ignoreMigrations();
        Cashier::useCustomerModel('App\\Models\\Shop');

        // Swallow SMTP / recipient errors system-wide for notification mails.
        $this->app->bind(
            \Illuminate\Notifications\Channels\MailChannel::class,
            \App\Notifications\Channels\SafeMailChannel::class
        );

        // Payment method binding for wallet deposit / checkout (not subscription billing)
        if ($this->shouldResolvePaymentBinding()) {
            $className = $this->resolvePaymentDependency((string) Request::get('payment_method'));
            $this->app->bind(PaymentServiceContract::class, $className);
        }

        $this->app->singleton(\App\Services\Emola\EmolaClient::class);

        // On demand Image manipulation
        $this->app->singleton(
            \League\Glide\Server::class,
            function ($app) {
                $filesystem = $app->make(Filesystem::class);

                return \League\Glide\ServerFactory::create([
                    'response' => new \League\Glide\Responses\SymfonyResponseFactory(app('request')),
                    'driver' => config('image.driver'),
                    'presets' => config('image.sizes'),
                    'source' => $filesystem->getDriver(),
                    'cache' => $filesystem->getDriver(),
                    'cache_path_prefix' => config('image.cache_dir'),
                    'base_url' => 'image', // Don't change this value
                ]);
            }
        );
    }

    /**
     * Whether the current request should bind PaymentServiceContract from payment_method.
     * Subscription billing uses wallet/mpesa/emola as a billing choice, not a checkout gateway.
     */
    private function shouldResolvePaymentBinding(): bool
    {
        if (! Request::has('payment_method')) {
            return false;
        }

        $path = Request::path();

        // Subscription billing resolves mobile money via SubscriptionMobilePaymentService.
        foreach ([
            'account/subscribe',
            'admin/account/subscribe',
            'api/vendor/subscription',
            '/subscribe/',
        ] as $segment) {
            if (stripos($path, $segment) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve the payment dependency based on the given class/paymentGateway name.
     *
     * @param  string  $class_name  Payment gateway name.
     * @return string Fully qualified class name.
     *
     * @throws \ErrorException
     */
    private function resolvePaymentDependency(string $class_name): string
    {
        // Mapping of payment gateways to their respective service classes
        $paymentServices = [
            'stripe' => [
                'default' => \App\Services\Payments\StripeWebPaymentService::class,
                'wallet' => \App\Services\Payments\StripePaymentService::class,
            ],
            'saved_card' => \App\Services\Payments\StripePaymentService::class,
            'paypal' => \App\Services\Payments\PaypalPaymentService::class,
            'wire' => \App\Services\Payments\WirePaymentService::class,
            'cod' => \App\Services\Payments\CodPaymentService::class,
            'zcart-wallet' => \Incevio\Package\Wallet\Services\WalletPaymentService::class,
            'mpesa' => \Incevio\Package\MPesa\Services\MPesaPaymentService::class,
            'emola' => \App\Services\Payments\EmolaPaymentService::class,
        ];

        // Special handling for Stripe to differentiate between wallet deposits
        if ($class_name === 'stripe') {
            return stripos(request()->path(), 'wallet/deposit') !== false
                ? $paymentServices['stripe']['wallet']
                : $paymentServices['stripe']['default'];
        }

        // Lookup the class name in the array
        if (isset($paymentServices[$class_name])) {
            return $paymentServices[$class_name];
        }

        // Throw an error if the payment method is not found
        throw new \ErrorException("Error: Payment Method {$class_name} Not Found.");
    }

    /**
     * Ensure storage/framework directories exist and are writable (sessions, views, cache).
     * Fixes "Failed to open stream: No such file or directory" on fresh or deployed environments.
     */
    private function ensureStorageFrameworkDirectoriesExist(): void
    {
        $paths = [
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
        ];

        foreach ($paths as $path) {
            if (! File::isDirectory($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }
    }
}
