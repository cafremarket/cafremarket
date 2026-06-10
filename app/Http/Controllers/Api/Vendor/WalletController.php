<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendorShop;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Support\Facades\Storage;
use Incevio\Package\Wallet\Http\Resources\TransactionResource;
use Incevio\Package\Wallet\Http\Resources\WalletResource;
use Incevio\Package\Wallet\Models\Transaction;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalletController extends Controller
{
    use ResolvesVendorShop;

    protected function assertOwnsTransaction(Transaction $transaction): void
    {
        $shop = $this->shop();

        abort_unless(
            $transaction->payable_type === Shop::class
            && (int) $transaction->payable_id === (int) $shop->id,
            403
        );
    }

    public function index()
    {
        $shop = $this->shop();

        try {
            return new WalletResource($shop);
        } catch (\Throwable $e) {
            return response()->json([
                'data' => [
                    'balance'     => get_formated_currency(0),
                    'balance_raw' => 0,
                ],
            ]);
        }
    }

    public function transactions()
    {
        $shop = $this->shop();
        $transactions = $shop->transactions()->latest()->paginate(
            config('mobile_app.view_listing_per_page', 8)
        );

        return TransactionResource::collection($transactions);
    }

    public function invoice(Transaction $transaction)
    {
        $this->assertOwnsTransaction($transaction);

        return $transaction->invoice('download');
    }

    public function payoutProof(Transaction $transaction): StreamedResponse
    {
        $this->assertOwnsTransaction($transaction);

        abort_unless($transaction->hasPayoutPaymentProof(), 404);

        $path = $transaction->payoutPaymentProofStoragePath();

        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $path,
            $transaction->payoutPaymentProofName()
        );
    }
}
