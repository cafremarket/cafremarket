<?php

namespace Incevio\Package\Wallet\Commands;

use App\Models\Shop;
use Exception;
use Illuminate\Console\Command;
// use App\Services\DbService;
use Illuminate\Support\Facades\Log;
use Incevio\Package\Wallet\Jobs\SendNotificationJob;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Notifications\PeriodicPayoutCreated;

class PeriodicPayout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:payout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create payout for approved sales amounts monthly';

    /**
     * @throws
     */
    public function handle(): void
    {
        if (is_incevio_package_loaded('wallet')) {            // Check approved balance
            $meta = ['type' => Transaction::TYPE_PAYOUT, 'description' => trans('packages.wallet.periodic_payout_created', ['period' => config('system.order.vendor_get_paid')])];

            $shops = Shop::active()->get();

            $shops->each(function ($shop) use ($meta) {
                if ($shop->wallet->balance >= get_min_withdrawal_limit()) {
                    try {
                        $trans = $shop->withdraw($shop->wallet->balance, $meta, true, false);

                        if (
                            is_incevio_package_loaded('dynamicCommission')
                            && get_from_option_table('dynamicCommission_reset_on_payout')
                        ) {
                            $shop->forcefill(['periodic_sold_amount' => 0]);
                            $shop->save();
                        }

                        SendNotificationJob::dispatch($trans, PeriodicPayoutCreated::class);

                        Log::channel('wallet')->info(config('system.order.vendor_get_paid').' Payout successfully created.');
                    } catch (Exception $exception) {
                        Log::channel('wallet')->error(config('system.order.vendor_get_paid').' Payout Error:: ');
                        Log::channel('wallet')->info($exception);
                    }
                } else {
                    Log::channel('wallet')->error($shop->name.' payout Limit Error::');
                    Log::channel('wallet')->info(trans('packages.wallet.minimum_payout_limit_amount', ['amount' => get_min_withdrawal_limit()]));
                }
            });
        } else {
            Log::channel('wallet')->error('Package is disabled::');
            Log::channel('wallet')->info(trans('message.package_inactive', ['package' => 'Wallet']));
        }
    }
}
