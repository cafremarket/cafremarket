<?php

namespace Incevio\Package\Wallet\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Incevio\Package\Wallet\Http\Resources\TransactionResource;
use Incevio\Package\Wallet\Http\Resources\WalletResource;
use Incevio\Package\Wallet\Models\Transaction;

class WalletController extends Controller
{
    /**
     * Getting wallet balance for customer
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $wallet = Auth::guard('api')->user()->wallet;
        } catch (\Exception $e) {
            return null;
        }

        return new WalletResource($wallet);
    }

    /**
     * Getting transactions for customer
     *
     * @return \Illuminate\Http\Response
     */
    public function transactions()
    {
        try {
            $wallet = Auth::guard('api')->user()->wallet;
            $transactions = $wallet->transactions->paginate(5);
        } catch (\Exception $e) {
            return null;
        }

        return TransactionResource::collection($transactions);
    }

    /**
     * Download transactions pdf invoice
     *
     * @return \Illuminate\Http\Response
     */
    public function invoice(Transaction $transaction)
    {
        abort_unless($transaction->payable_type === \App\Models\Customer::class
            && (int) $transaction->payable_id === (int) \Illuminate\Support\Facades\Auth::guard('api')->id(), 404);

        return $transaction->customerInvoice('download');
    }
}
