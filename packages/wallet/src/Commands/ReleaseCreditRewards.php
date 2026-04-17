<?php

namespace Incevio\Package\Wallet\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Incevio\Package\Wallet\Models\CreditReward;

class ReleaseCreditRewards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:release-rewards';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check settings and release credit rewards into customers wallet balance.';

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
        $rewards = CreditReward::releasable()->get();

        foreach ($rewards as $reward) {
            if ($reward->customer_id && $reward->shop_id) {
                try {
                    $reward->release();
                } catch (Exception $exception) {
                    Log::channel('wallet')->error('Reward Releasing Error for order:: '.$reward->order->order_number);
                    Log::channel('wallet')->info($exception);
                }
            }
        }

        Log::channel('wallet')->info('All pending rewards has been released successfully.');
    }
}
