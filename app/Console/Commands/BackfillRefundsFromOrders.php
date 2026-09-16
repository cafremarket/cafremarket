<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class BackfillRefundsFromOrders extends Command
{
    protected $signature = 'refunds:backfill-from-orders';

    protected $description = 'Create missing refunds rows for orders already marked refunded / partially refunded';

    public function handle(): int
    {
        $this->info('Backfilling refund records from refunded orders...');

        $created = Order::backfillMissingRefundRecords();

        $this->info("Created {$created} refund record(s).");

        return self::SUCCESS;
    }
}
