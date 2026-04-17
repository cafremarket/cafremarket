<?php

namespace Incevio\Package\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Incevio\Package\Wallet\Http\Requests\WithdrawalRequest;
use Incevio\Package\Wallet\Jobs\SendNotificationJob;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Notifications\Pending;

class WithdrawalController extends Controller
{
    private $wallet;

    /**
     * constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function ($request, $next) {
            if (Auth::guard('affiliate')->check()) {
                $this->wallet = Auth::guard('affiliate')->user()->wallet;
            } else {
                $this->wallet = Auth::guard('web')->user()->shop;
            }

            return $next($request);
        });
    }

    /**
     * Show the withdrawal form
     *
     * @return response
     */
    public function form(Request $request)
    {
        $minimum = get_min_withdrawal_limit();

        $balance = $this->wallet->balance;

        return view('wallet::_withdraw', compact('balance', 'minimum'));
    }

    /**
     * Submit the withdrawal request
     *
     * @return response
     */
    public function withdraw(WithdrawalRequest $request)
    {
        $meta = [
            'type' => Transaction::TYPE_PAYOUT,
            'description' => trans('packages.wallet.payout_requested'),
        ];

        $transaction = $this->wallet->withdraw($request->amount, $meta, false, false);

        SendNotificationJob::dispatch($transaction, Pending::class); // Sent notification to the wallet owner

        $route = Auth::guard('affiliate')->check() ? 'affiliate.wallet' : 'merchant.wallet'; // Determine redirect route

        return redirect()->route($route)
            ->with('success', trans('packages.wallet.payout_requested'));
    }
}
