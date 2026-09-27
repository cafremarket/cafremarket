<?php

namespace App\Console\Commands;

use App\Services\Affiliate\AffiliateCommissionRelease;
use Illuminate\Console\Command;

/**
 * Credit affiliate commissions whose item's refund/return period has ended.
 */
class ReleaseAffiliateCommissions extends Command
{
    protected $signature = 'affiliate:release-commissions';

    protected $description = 'Credit affiliate commissions once the refund/return period of the order item has ended';

    public function handle(AffiliateCommissionRelease $release): int
    {
        $stats = $release->releaseDue();

        $this->info(sprintf(
            'Paid: %d, voided (refunded): %d, held (open claim): %d, failed: %d',
            $stats['paid'], $stats['voided'], $stats['held'], $stats['failed']
        ));

        return self::SUCCESS;
    }
}
