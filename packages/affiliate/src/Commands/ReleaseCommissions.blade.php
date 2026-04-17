<?php

namespace Incevio\Package\Affiliate\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Affiliate\Models\AffiliateCommission;

class ReleaseCommissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'affiliate:release-commissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the holding dates of affiliate commission payments and release the pending commissions.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!is_incevio_package_loaded('wallet')) {
            Log::channel('wallet')->error('Affiliate commission payout failed Beacause the Wallet plugin is not installed/active');

            return;
        }

        $release_in_days = config('system_settings.affiliate_commission_release_in_days');
        
        if (!isset($release_in_days)) {
            return; // If the release days is set to null, commissions can only be manually released.
        }

        $unpaid_commissions = AffiliateCommission::unpaid()
                                                ->where('created_at', '<=', now()->subDays($release_in_days))
                                                ->get();

        foreach ($unpaid_commissions as $commission) {
            try {
                if ($commission->order->isPaid())
                {
                    $commission->markAsPaid();
                }
            } catch (Exception $e) {
                Log::channel('wallet')->error('Affiliate commission payout failed for Error: ' . $e->getMessage());
            }
        }

        Log::channel('wallet')->info('All payable affiliate commission has been released successfully.');
    }
}
