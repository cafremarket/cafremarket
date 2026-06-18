<?php

namespace Incevio\Package\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Incevio\Package\Wallet\Http\Requests\WithdrawalRequest;
use Incevio\Package\Wallet\Jobs\SendNotificationJob;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Notifications\Pending;

class WithdrawalController extends Controller
{
    private $wallet;

    private $shop;

    /**
     * constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function ($request, $next) {
            if (Auth::guard('affiliate')->check()) {
                $this->wallet = Auth::guard('affiliate')->user()->wallet;
                $this->shop = null;
            } else {
                $this->shop = Auth::guard('web')->user()->shop;
                $this->wallet = $this->shop;
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
        $existing_instruction = $this->shop?->pay_to;

        if (Auth::guard('affiliate')->check()) {
            $existing_instruction = Auth::guard('affiliate')->user()->pay_to;
        }

        return view('wallet::_withdraw', compact('balance', 'minimum', 'existing_instruction'));
    }

    /**
     * Submit the withdrawal request
     *
     * @return response
     */
    public function withdraw(WithdrawalRequest $request)
    {
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

        if ($this->shop instanceof Shop) {
            $this->shop->pay_to = $instruction;
            $this->shop->save();
        } elseif (Auth::guard('affiliate')->check()) {
            $affiliate = Auth::guard('affiliate')->user();
            $affiliate->pay_to = $instruction;
            $affiliate->save();
        }

        $transaction = $this->wallet->withdraw($request->amount, $meta, false, false);

        SendNotificationJob::dispatch($transaction, Pending::class);

        $route = Auth::guard('affiliate')->check() ? 'affiliate.wallet' : 'merchant.wallet';

        return redirect()->route($route)
            ->with('success', trans('packages.wallet.payout_requested'));
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
