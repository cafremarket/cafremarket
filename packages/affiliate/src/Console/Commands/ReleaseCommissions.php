<?php

namespace Incevio\Package\Affiliate\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! is_incevio_package_loaded('wallet')) {
            Log::channel('wallet')->error('Affiliate commission payout failed because the Wallet plugin is not installed/active');

            return self::FAILURE;
        }

        $release_in_days = config('system_settings.affiliate_commission_release_in_days');

        if (! isset($release_in_days)) {
            return self::SUCCESS;
        }

        $unpaid_commissions = AffiliateCommission::unpaid()
            ->with(['order', 'affiliate'])
            ->where('created_at', '<=', now()->subDays($release_in_days))
            ->get();

        foreach ($unpaid_commissions as $commission) {
            try {
                if ($commission->order && $commission->order->isPaid()) {
                    $commission->markAsPaid();
                }
            } catch (Exception $e) {
                Log::channel('wallet')->error('Affiliate commission payout failed for Error: '.$e->getMessage());
            }
        }

        Log::channel('wallet')->info('Affiliate commission release job completed.');

        return self::SUCCESS;
    }
}
