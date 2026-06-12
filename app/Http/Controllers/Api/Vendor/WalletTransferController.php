<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendorShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\VendorWalletTransferRequest;
use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Support\Facades\Log;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Services\CommonService;

class WalletTransferController extends Controller
{
    use ResolvesVendorShop;

    public function vendors()
    {
        $shop = $this->shop();

        $vendors = Shop::approved()
            ->where('id', '!=', $shop->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $vendors], 200);
    }

    public function transfer(VendorWalletTransferRequest $request)
    {
        $shop = $this->shop();
        $email = strtolower(trim((string) $request->input('email')));

        if (strtolower((string) $shop->email) === $email) {
            return response()->json([
                'message' => trans('packages.wallet.email_not_found'),
            ], 400);
        }

        $recipientType = (string) $request->input('recipient_type');
        $toHolder = $recipientType === 'customer'
            ? Customer::where('email', $request->email)->first()
            : Shop::where('email', $request->email)->first();

        if (! $toHolder) {
            return response()->json([
                'message' => trans('packages.wallet.email_not_found'),
            ], 400);
        }

        try {
            $meta = [
                'from' => [
                    'type' => Transaction::TYPE_WITHDRAW,
                    'to' => $toHolder->email,
                    'description' => trans('packages.wallet.balance_sent_to', [
                        'email' => $request->email,
                    ]),
                ],
                'to' => [
                    'type' => Transaction::TYPE_DEPOSIT,
                    'from' => $shop->email,
                    'description' => trans('packages.wallet.balance_received_from', [
                        'email' => $shop->email,
                    ]),
                ],
            ];

            (new CommonService)->transfer(
                $shop->wallet,
                $toHolder->wallet,
                $request->amount,
                $meta
            );
        } catch (\Exception $exception) {
            Log::error('Vendor wallet transfer failed');
            Log::info($exception);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 400);
        }

        return response()->json([
            'message' => trans('packages.wallet.transfer_success'),
        ], 200);
    }
}
