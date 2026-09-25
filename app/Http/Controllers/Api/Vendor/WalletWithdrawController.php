<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendorShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\VendorWalletWithdrawRequest;
use App\Notifications\SuperAdmin\WithdrawalRequested;
use App\Services\Wallet\ShopPayoutAccount;
use Incevio\Package\Wallet\Jobs\SendNotificationJob;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Notifications\Pending;

class WalletWithdrawController extends Controller
{
    use ResolvesVendorShop;

    /**
     * The vendor's registered (locked) payout account, or null if none yet.
     */
    public function payoutAccount()
    {
        return response()->json([
            'data' => ShopPayoutAccount::summary($this->shop()),
        ]);
    }

    public function withdraw(VendorWalletWithdrawRequest $request)
    {
        $shop = $this->shop();

        // Registers the account on the first request; later requests always use the locked one.
        $account = ShopPayoutAccount::resolveForWithdrawal($shop, $request);

        $meta = [
            'type' => Transaction::TYPE_PAYOUT,
            'description' => trans('packages.wallet.payout_requested'),
            'payout_method' => $account['method'],
            'payout_details' => $account['details'],
            'payout_instruction' => $account['instruction'],
        ];

        $transaction = $shop->withdraw($request->amount, $meta, false, false);

        SendNotificationJob::dispatch($transaction, Pending::class);
        WithdrawalRequested::notifyAdmins($transaction);

        return response()->json([
            'message' => trans('packages.wallet.payout_requested'),
            'payout_account' => ShopPayoutAccount::summary($shop),
        ], 200);
    }
}
