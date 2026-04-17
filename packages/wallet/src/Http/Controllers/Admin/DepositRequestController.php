<?php

namespace Incevio\Package\Wallet\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Incevio\Package\Wallet\Http\Requests\WithdrawalActionsRequest;
use Incevio\Package\Wallet\Jobs\SendNotificationJob;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Notifications\Approve;
use Incevio\Package\Wallet\Notifications\Declined;

class DepositRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $payout_requests = Transaction::withdrawals()->pending()
            ->with('payable:id,name', 'wallet:id')->orderBy('created_at', 'desc')->get();

        return view('wallet::admin.payout_requests', compact('payout_requests'));
    }

    public function show_form(Transaction $transaction)
    {
        return view('wallet::admin._approve', compact('transaction'));
    }

    public function approve(WithdrawalActionsRequest $request, Transaction $transaction)
    {
        try {
            $transaction->approve($request->fee);

            // Dispatch Job
            SendNotificationJob::dispatch($transaction, Approve::class);

            return back()->with('success', trans('packages.wallet.payout_approved'));
        } catch (\Exception $exception) {
            return back()->with('warning', $exception->getMessage());
        }
    }

    public function decline(WithdrawalActionsRequest $request, Transaction $transaction)
    {
        try {
            $transaction->decline();

            // Dispatch Job
            SendNotificationJob::dispatch($transaction, Declined::class);

            return back()->with('success', trans('packages.wallet.payout_declined'));
        } catch (\Exception $exception) {
            return back()->with('warning', $exception->getMessage());
        }
    }
}
