<?php

namespace Incevio\Package\Wallet\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Incevio\Package\Wallet\Models\Transaction;

class ReleasePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:release-payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the holding dates of payments and release the pending balance.';

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
        $transactions = Transaction::escrowed()->get();

        foreach ($transactions as $transaction) {
            if (isset($transaction->meta['order_id'])) {
                try {
                    $transaction->confirmed = 1;
                    $transaction->save();

                    // Add balance to wallet
                    $wallet = $transaction->wallet;
                    $wallet->balance = ($wallet->balance + $transaction->amount);
                    $wallet->save();
                } catch (Exception $exception) {
                    Log::channel('wallet')->error('Escrowed Payout Error for transaction:: '.$transaction->id);
                    Log::channel('wallet')->info($exception);
                }
            }
        }

        Log::channel('wallet')->info('All payable escrowed order payment has been released successfully.');
    }
}
