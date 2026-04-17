<?php

namespace Incevio\Package\Wallet\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Incevio\Package\Wallet\Models\CreditReward;
use Incevio\Package\Wallet\Models\Wallet;

class CreditRewardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $creditRewards = CreditReward::with('order', 'customer')->orderBy('created_at', 'desc')->get();

        return view('wallet::admin.credit_rewards', compact('creditRewards'));
    }

    /**
     * Release the credit
     *
     * @return \Illuminate\Http\Response
     */
    public function release(Request $request, CreditReward $creditReward)
    {
        Gate::authorize('payout', Wallet::class);

        try {
            $creditReward->release();

            return redirect()->back()->with('success', trans('packages.wallet.approved'));
        } catch (\Exception $exception) {
            return redirect()->back()->with('warning', $exception->getMessage());
        }
    }

    /**
     * Delete the record
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, CreditReward $creditReward)
    {
        Gate::authorize('payout', Wallet::class);

        $creditReward->delete();

        return redirect()->back()->with('success', trans('app.data_deleted'));
    }
}
