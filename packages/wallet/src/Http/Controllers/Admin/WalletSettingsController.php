<?php

namespace Incevio\Package\Wallet\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Incevio\Package\Wallet\Models\Wallet;

class WalletSettingsController extends Controller
{
    /** @var list<string> */
    private const PLATFORM_FEE_OPTION_KEYS = [
        'platform_fee_mpesa_enabled',
        'platform_fee_mpesa_type',
        'platform_fee_mpesa_value',
        'platform_fee_emola_enabled',
        'platform_fee_emola_type',
        'platform_fee_emola_value',
        'platform_fee_payout_enabled',
        'platform_fee_payout_type',
        'platform_fee_payout_value',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Gate::authorize('setting', Wallet::class);

        $paymentMethods = PaymentMethod::online()->active()->pluck('name', 'id')->toArray();

        return view('wallet::admin.settings', compact('paymentMethods'));
    }

    public function platformFees()
    {
        Gate::authorize('setting', Wallet::class);

        return view('wallet::admin.platform_fees');
    }

    public function updatePlatformFees(Request $request)
    {
        Gate::authorize('setting', Wallet::class);

        foreach (self::PLATFORM_FEE_OPTION_KEYS as $key) {
            if (! $request->has($key)) {
                continue;
            }

            update_or_create_option_table_record($key, $request->input($key));
            Cache::forget($key);
        }

        return redirect()
            ->route('admin.wallet.platform_fees')
            ->with('success', trans('packages.wallet.platform_fees_updated'));
    }
}
