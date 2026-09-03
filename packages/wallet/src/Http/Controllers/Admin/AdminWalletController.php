<?php

namespace Incevio\Package\Wallet\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Incevio\Package\Wallet\Exceptions\WalletOwnerInvalid;
use Incevio\Package\Wallet\Http\Requests\AdminCreateWalletRequest;
use Incevio\Package\Wallet\Http\Requests\AdminWalletTopupRequest;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Models\Wallet;
use Incevio\Package\Wallet\Traits\HasTransaction;

class AdminWalletController extends Controller
{
    use HasTransaction;

    /**
     * List all active (non-blocked) wallets.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Gate::authorize('payout', Wallet::class);

        $query = Wallet::with('holder')
            ->where(function ($q) {
                $q->where('blocked', false)->orWhereNull('blocked');
            });

        // Default: show all wallets. Pass has_balance=1 to filter positive balances only.
        if ($request->get('has_balance', '0') === '1') {
            $query->where('balance', '>', 0);
        }

        $type = $request->get('type');
        if ($type === 'customer') {
            $query->where('holder_type', Customer::class);
        } elseif ($type === 'merchant') {
            $query->where('holder_type', Shop::class);
        }

        $search = trim((string) $request->get('q'));
        if ($search !== '') {
            $query->whereHasMorph('holder', [Customer::class, Shop::class], function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $wallets = $query->orderByDesc('balance')
            ->paginate(50)
            ->appends($request->query());

        return view('wallet::admin.wallets', compact('wallets'));
    }

    /**
     * Show admin top-up modal form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showTopupForm(Request $request)
    {
        Gate::authorize('payout', Wallet::class);

        $email = old('email');
        $userType = old('user_type', 'customer');

        if ($request->filled('wallet_id')) {
            $wallet = Wallet::with('holder')->find($request->get('wallet_id'));
            if ($wallet && $wallet->holder) {
                $email = $wallet->holder->email ?? $email;
                $userType = $wallet->holder instanceof Shop ? 'merchant' : 'customer';
            }
        }

        return view('wallet::admin._topup', compact('email', 'userType'));
    }

    /**
     * Credit a customer or merchant wallet (admin top-up).
     *
     * @return \Illuminate\Http\Response
     */
    public function topup(AdminWalletTopupRequest $request)
    {
        Gate::authorize('payout', Wallet::class);

        try {
            $holder = $this->getWallet($request->user_type, $request->email);

            if (! $holder) {
                throw new WalletOwnerInvalid(
                    trans('packages.wallet.wallet_email_not_found', ['email' => $request->email])
                );
            }

            $admin = Auth::user();
            $meta = [
                'type' => Transaction::TYPE_DEPOSIT,
                'email' => $request->email,
                'description' => $request->filled('description')
                    ? $request->description
                    : trans('packages.wallet.admin_topup_description'),
                'admin_manual' => true,
                'admin_id' => $admin ? $admin->id : null,
                'admin_name' => $admin ? (method_exists($admin, 'getName') ? $admin->getName() : $admin->name) : null,
            ];

            $holder->deposit((float) $request->amount, $meta);

            return redirect()
                ->route('admin.wallet.list')
                ->with('success', trans('packages.wallet.admin_topup_success'));
        } catch (\Exception $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('warning', $exception->getMessage());
        }
    }

    /**
     * Show create-wallet form (customer or store).
     */
    public function showCreateForm()
    {
        Gate::authorize('payout', Wallet::class);

        return view('wallet::admin._create_wallet');
    }

    /**
     * Create an empty wallet for a customer or store if missing.
     */
    public function create(AdminCreateWalletRequest $request)
    {
        Gate::authorize('payout', Wallet::class);

        try {
            $holder = $this->getWallet($request->user_type, $request->email);

            if (! $holder) {
                throw new WalletOwnerInvalid(
                    trans('packages.wallet.wallet_email_not_found', ['email' => $request->email])
                );
            }

            $wallet = app(\Incevio\Package\Wallet\Services\WalletService::class)
                ->getWallet($holder, true);

            return redirect()
                ->route('admin.wallet.list')
                ->with('success', trans('packages.wallet.create_wallet_success', [
                    'owner' => method_exists($holder, 'getName') ? $holder->getName() : ($holder->name ?? $request->email),
                    'balance' => get_formated_currency($wallet->balance, 2),
                ]));
        } catch (\Exception $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('warning', $exception->getMessage());
        }
    }

    /**
     * Wallet transaction logs (all wallets or a single wallet).
     *
     * @return \Illuminate\Http\Response
     */
    public function transactions(Request $request)
    {
        Gate::authorize('payout', Wallet::class);

        $query = Transaction::with(['payable', 'wallet.holder'])
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('wallet_id')) {
            $query->where('wallet_id', $request->get('wallet_id'));
        }

        $search = trim((string) $request->get('q'));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('meta', 'like', "%{$search}%")
                    ->orWhereHasMorph('payable', [Customer::class, Shop::class], function ($pq) use ($search) {
                        $pq->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->paginate(50)->appends($request->query());

        $wallet = $request->filled('wallet_id')
            ? Wallet::with('holder')->find($request->get('wallet_id'))
            : null;

        return view('wallet::admin.wallet_transactions', compact('transactions', 'wallet'));
    }
}
