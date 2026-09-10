<?php

namespace Incevio\Package\Wallet\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Gate;
use Incevio\Package\Wallet\Models\Wallet;

class WalletSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Gate::authorize('setting', Wallet::class);

        // Deposit top-up: M-Pesa and eMola only (not Cafrepay wallet).
        $paymentMethods = PaymentMethod::online()
            ->active()
            ->whereIn('code', ['mpesa', 'emola'])
            ->pluck('name', 'id')
            ->toArray();

        return view('wallet::admin.settings', compact('paymentMethods'));
    }

}
