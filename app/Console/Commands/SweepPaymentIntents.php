<?php

namespace App\Console\Commands;

use App\Services\Payments\CheckoutPaymentIntentService;
use Illuminate\Console\Command;

/**
 * Settle mobile-money checkouts the customer left: query the gateway, create
 * orders for confirmed payments and expire the rest.
 */
class SweepPaymentIntents extends Command
{
    protected $signature = 'payments:sweep-intents';

    protected $description = 'Confirm or expire pending mobile-money checkout payments (orders are created only when paid)';

    public function handle(CheckoutPaymentIntentService $intents): int
    {
        // Intents are written by web requests in the system timezone; the CLI starts in UTC.
        setSystemConfig();

        $this->info('Checked: '.$intents->sweep());

        return self::SUCCESS;
    }
}
