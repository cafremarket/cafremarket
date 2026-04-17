<?php

namespace Incevio\Package\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Incevio\Package\Wallet\Http\Requests\TransferRequest;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Services\CommonService;
use Incevio\Package\Wallet\Traits\HasTransaction;

class TransferController extends Controller
{
    use HasTransaction;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show_form(Request $request)
    {
        $wallet = self::getWallet();

        $shops_email_list = Shop::approved()->select('name', 'email')->orderBy('name', 'asc')->get();

        if (Auth::guard('customer')->check()) {
            $tab = 'wallet';

            $content = view('wallet::customer._transfer', compact('wallet', 'shops_email_list'))->render();

            return view('theme::dashboard', compact('tab', 'content'));
        }

        return view('wallet::_transfer', compact('wallet', 'shops_email_list'));
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     *                                                                                                                         for transfer to own customer or merchant account
     */
    public function show_self_form(Request $request)
    {
        $wallet = self::getWallet();

        if (Auth::guard('customer')->check()) {
            $tab = 'wallet';

            $content = view('wallet::customer._self_transfer', compact('wallet'))->render();

            return view('theme::dashboard', compact('tab', 'content'));
        }

        return view('wallet::_self_transfer', compact('wallet'));
    }

    /**
     * Transfer balance to Other Customer
     *
     * @return RedirectResponse
     */
    public function transfer(TransferRequest $request)
    {
        $user_type = Auth::guard('customer')->check() ? 'customer' : 'shop';

        if (! customer_can_register() && ! $request->has('email')) { // For self transfer
            if ($user_type == 'customer') {
                $email = Auth::guard('customer')->user()->email;
                $toWallet = Shop::where('email', $email)->first();
            } elseif ($user_type == 'shop') {
                $email = Auth::guard('web')->user()->shop->email;
                $toWallet = Customer::where('email', $email)->first();
            }
        } elseif ($request->has('recipient_type')) {
            $toWallet = self::getWallet($user_type, $request->email, request('recipient_type'));
        } else {
            $toWallet = self::getWallet($user_type, $request->email);
        }

        if (! $toWallet) {
            return redirect()->back()->withInput()
                ->with('warning', trans('packages.wallet.email_not_found'));
        }

        try {
            $fromWallet = self::getWallet();

            $meta = self::getMeta($request, $toWallet, $fromWallet);

            (new CommonService)->transfer($fromWallet->wallet, $toWallet->wallet, $request->amount, $meta);
        } catch (\Exception $exception) {
            Log::error('Transfer failed:: ');
            Log::info($exception);

            return redirect()->route(self::getRouteName())
                ->with('warning', $exception->getMessage())->withInput();
        }

        return redirect()->route(self::getRouteName('wallet'))
            ->with('success', trans('packages.wallet.transfer_success'));
    }

    /**
     * get Formatted meta:
     *
     * @param  $customer
     * @return array[]
     */
    private function getMeta($request, $to, $from)
    {
        return [
            'from' => [
                'type' => Transaction::TYPE_WITHDRAW,
                'to' => $to->email,
                'description' => trans('packages.wallet.balance_sent_to', ['email' => $request->email]),
            ],
            'to' => [
                'type' => Transaction::TYPE_DEPOSIT,
                'from' => $from->email,
                'description' => trans('packages.wallet.balance_received_from', ['email' => $from->email]),
            ],
        ];
    }
}
