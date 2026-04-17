<?php

namespace Incevio\Package\Wallet\Http\Controllers\Admin;

use App\Helpers\ListHelper;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Incevio\Package\Wallet\Http\Requests\PayoutRequest;
use Incevio\Package\Wallet\Jobs\SendNotificationJob;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Models\Wallet;
use Incevio\Package\Wallet\Notifications\Created;

class PayoutController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Transaction $transaction)
    {
        Gate::authorize('payout', Wallet::class);

        $payouts = Transaction::with('payable')->payouts()->complete()->orderBy('created_at', 'desc')->get();

        return view('wallet::admin.payouts', compact('payouts'));
    }

    public function show_form(Request $request)
    {
        $shops = ListHelper::shops();

        return view('wallet::admin._payout', compact('shops'));
    }

    public function payout(PayoutRequest $request, Transaction $transaction)
    {
        try {
            $meta = [
                'type' => $transaction::TYPE_PAYOUT,
                'description' => trans('packages.wallet.payout_desc', ['platform' => get_platform_title()]),
                'fee' => $request->fee,
            ];

            $amount = ($request->amount + $request->fee);
            $trans = Shop::find($request->shop_id)->withdraw($amount, $meta, true, true);

            // Dispatch Job
            SendNotificationJob::dispatch($trans, Created::class);

            return redirect()->back()->with('success', trans('packages.wallet.payout_approved'));
        } catch (\Exception $exception) {
            return redirect()->back()->with('warning', $exception->getMessage());
        }
    }
}
