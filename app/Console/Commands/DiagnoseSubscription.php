<?php

namespace App\Console\Commands;

use App\Models\SystemConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DiagnoseSubscription extends Command
{
    protected $signature = 'subscription:diagnose';

    protected $description = 'Check subscription billing configuration for production troubleshooting';

    public function handle(): int
    {
        $billing = config('system.subscription.billing');
        $enabled = config('system.subscription.enabled');
        $envBilling = env('SUBSCRIPTION_BILLING', '(not set)');

        $this->info('Subscription diagnose');
        $this->line('Environment: '.app()->environment());
        $this->line('SUBSCRIPTION_ENABLED: '.($enabled ? 'true' : 'false'));
        $this->line('SUBSCRIPTION_BILLING (.env): '.$envBilling);
        $this->line('config(system.subscription.billing): '.$billing);
        $this->line('Wallet packages loaded: '.(is_incevio_package_loaded(['wallet', 'subscription']) ? 'yes' : 'no'));

        try {
            $this->line('isBillingThroughWallet(): '.(SystemConfig::isBillingThroughWallet() ? 'yes' : 'no'));
        } catch (\Throwable $e) {
            $this->error('isBillingThroughWallet() error: '.$e->getMessage());
        }

        $columns = Schema::getColumnListing('subscriptions');
        $this->line('subscriptions columns: '.implode(', ', $columns));

        $this->newLine();
        $this->comment('If .env and config differ, run: php artisan config:clear');
        $this->comment('After deploy, also run: php artisan view:clear && php artisan cache:clear');

        return self::SUCCESS;
    }
}
