<?php

namespace App\Http\Controllers\Storefront;

use App\Helpers\ReCaptcha;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryBoy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Public pages the app stores ask for, for customers, sellers and delivery
 * people (including accounts made in the apps):
 *  - /account/delete         delete the whole account
 *  - /account/data-deletion  delete some data but keep the account
 * The person proves ownership with email + password. Account deletion works
 * the same way as the apps' in-app delete.
 */
class AccountDeletionController extends Controller
{
    public const TYPES = ['customer', 'seller', 'delivery'];

    /** Data a person can remove without closing the account, per account type. */
    public const DATA_OPTIONS = [
        'customer' => ['photo', 'addresses', 'sessions'],
        'seller' => ['photo', 'addresses', 'sessions'],
        'delivery' => ['photo', 'addresses', 'location', 'sessions'],
    ];

    public function show(Request $request)
    {
        return view('theme::account_deletion', [
            'type' => $this->typeFrom($request),
            'title' => trans('account_deletion.title'),
        ]);
    }

    public function showData(Request $request)
    {
        return view('theme::data_deletion', [
            'type' => $this->typeFrom($request),
            'options' => self::DATA_OPTIONS,
            'title' => trans('account_deletion.data_title'),
        ]);
    }

    public function destroy(Request $request)
    {
        $this->validateCredentials($request, [
            'confirm' => 'accepted',
        ], [
            'confirm.accepted' => trans('account_deletion.confirm_required'),
        ]);

        $type = $request->input('type');

        $deleted = DB::transaction(function () use ($request, $type) {
            $accounts = $this->findAccounts($type, $request->input('email'), $request->input('password'));

            foreach ($accounts as $account) {
                $this->deleteAccount($type, $account);
            }

            return $accounts->isNotEmpty();
        });

        if (! $deleted) {
            return $this->notFound($request);
        }

        $request->session()->regenerate();

        return redirect()->route('account.deletion.form')->with('account_deleted', true);
    }

    public function destroyData(Request $request)
    {
        $type = $request->input('type');
        $allowed = self::DATA_OPTIONS[$type] ?? [];

        $this->validateCredentials($request, [
            'data' => 'required|array|min:1',
            'data.*' => 'in:'.implode(',', $allowed ?: ['none']),
        ], [
            'data.required' => trans('account_deletion.data_required'),
        ]);

        $items = array_values(array_unique($request->input('data')));

        $done = DB::transaction(function () use ($request, $type, $items) {
            $accounts = $this->findAccounts($type, $request->input('email'), $request->input('password'));

            foreach ($accounts as $account) {
                $this->deleteData($account, $items);
            }

            return $accounts->isNotEmpty();
        });

        if (! $done) {
            return $this->notFound($request);
        }

        Log::info('Data deleted via web data deletion page', ['type' => $type, 'items' => $items]);

        return redirect()->route('account.data_deletion.form', ['type' => $type])
            ->with('data_deleted', $items);
    }

    private function validateCredentials(Request $request, array $rules, array $messages): void
    {
        $request->validate([
            'type' => 'required|in:'.implode(',', self::TYPES),
            'email' => 'required|email|max:191',
            'password' => 'required|string|max:191',
        ] + $rules + ReCaptcha::rules(), $messages + ReCaptcha::messages());
    }

    /**
     * Accounts of this type that the email + password unlock. Empty when the
     * email is unknown or the password is wrong.
     */
    private function findAccounts(string $type, string $email, string $password): Collection
    {
        $email = trim($email);

        $candidates = match ($type) {
            'customer' => Customer::where('email', $email)->get(),
            // Platform staff (admins) can't use these pages.
            'seller' => User::where('email', $email)->get()->filter(fn ($user) => $user->isFromMerchant()),
            // A rider can be registered with several stores under the same email.
            'delivery' => DeliveryBoy::where('email', $email)->get(),
        };

        return $candidates
            ->filter(fn ($account) => ! empty($account->password) && Hash::check($password, $account->password))
            ->values();
    }

    private function deleteAccount(string $type, $account): void
    {
        $this->logoutIfCurrent($type, $account);

        if ($type === 'delivery') {
            // Same as the delivery app's in-app delete.
            $account->deleteAccount();

            Log::info('Account deleted via web deletion page', ['type' => $type, 'id' => $account->id]);

            return;
        }

        // An owner leaving takes the store offline, so customers can't keep
        // ordering from a shop nobody runs. Orders/invoices stay for records.
        if ($type === 'seller' && $account->isMerchant()) {
            $shop = $account->owns;
            if ($shop && $shop->exists && $shop->active) {
                $shop->active = false;
                $shop->save();
            }
        }

        // Same as the customer/seller apps (soft delete; login fails afterwards).
        $account->flushAddresses();
        $account->flushImages();
        $this->clearTokens($account);
        $account->delete();

        Log::info('Account deleted via web deletion page', ['type' => $type, 'id' => $account->id]);
    }

    private function deleteData($account, array $items): void
    {
        if (in_array('photo', $items, true)) {
            $account->flushImages();
        }

        if (in_array('addresses', $items, true)) {
            $account->flushAddresses();
        }

        if (in_array('location', $items, true)) {
            $account->current_latitude = null;
            $account->current_longitude = null;
            $account->last_location_at = null;
            $account->is_online = false;
        }

        if (in_array('sessions', $items, true)) {
            $this->clearTokens($account);
        }

        $account->save();
    }

    private function clearTokens($account): void
    {
        $account->api_token = null;
        if (array_key_exists('fcm_token', $account->getAttributes())) {
            $account->fcm_token = null;
        }
    }

    private function logoutIfCurrent(string $type, $account): void
    {
        $guard = ['customer' => 'customer', 'seller' => 'web', 'delivery' => 'delivery_boy'][$type];

        if (Auth::guard($guard)->id() === $account->id) {
            Auth::guard($guard)->logout();
        }
    }

    private function notFound(Request $request)
    {
        // Same message for unknown email and wrong password (no account probing).
        return back()->withInput($request->only('type', 'email', 'data'))
            ->withErrors(['email' => trans('account_deletion.not_found')]);
    }

    private function typeFrom(Request $request): string
    {
        return in_array($request->query('type'), self::TYPES, true) ? $request->query('type') : 'customer';
    }
}
