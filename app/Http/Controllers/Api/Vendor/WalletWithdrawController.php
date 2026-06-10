<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\VendorWalletWithdrawRequest;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Incevio\Package\Wallet\Jobs\SendNotificationJob;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Notifications\Pending;

class WalletWithdrawController extends Controller
{
    protected function shop(): Shop
    {
        $shop = Auth::guard('vendor_api')->user()->shop;
        abort_unless($shop, 403, trans('packages.wallet.owner_invalid'));

        return $shop;
    }

    public function withdraw(VendorWalletWithdrawRequest $request)
    {
        $shop = $this->shop();
        $payoutMethod = (string) $request->input('payout_method');
        $details = $this->payoutDetailsFromRequest($request);
        $instruction = format_payout_instruction_text($payoutMethod, $details);

        $meta = [
            'type' => Transaction::TYPE_PAYOUT,
            'description' => trans('packages.wallet.payout_requested'),
            'payout_method' => $payoutMethod,
            'payout_details' => $details,
            'payout_instruction' => $instruction,
        ];

        $shop->pay_to = $instruction;
        $shop->save();

        $transaction = $shop->withdraw($request->amount, $meta, false, false);

        SendNotificationJob::dispatch($transaction, Pending::class);

        return response()->json([
            'message' => trans('packages.wallet.payout_requested'),
        ], 200);
    }

    /**
     * @return array<string, string|null>
     */
    private function payoutDetailsFromRequest(Request $request): array
    {
        if (in_array($request->input('payout_method'), ['mpesa', 'emola'], true)) {
            return [
                'mobile' => trim((string) $request->input('payout_mobile')),
            ];
        }

        return [
            'bank_name' => trim((string) $request->input('payout_bank_name')),
            'account_holder' => trim((string) $request->input('payout_account_holder')),
            'account_number' => trim((string) $request->input('payout_account_number')),
        ];
    }
}
