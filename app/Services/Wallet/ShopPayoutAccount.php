<?php

namespace App\Services\Wallet;

use App\Models\Shop;
use Illuminate\Http\Request;

/**
 * A vendor's payout account (bank transfer, M-Pesa or eMola).
 *
 * For security and platform reputation, a shop withdraws to one fixed account:
 * it is registered with the first withdrawal request and locked from then on.
 * Changing it requires a support request; an admin then resets it and the next
 * withdrawal registers the new account.
 */
class ShopPayoutAccount
{
    public const METHODS = ['bank_transfer', 'mpesa', 'emola'];

    /**
     * Mozambique mobile-money prefixes: M-Pesa (Vodacom) 84/85, eMola (Movitel) 86/87.
     */
    private const MOBILE_PREFIXES = [
        'mpesa' => ['84', '85'],
        'emola' => ['86', '87'],
    ];

    /**
     * Validation rules for the payout account fields of a withdrawal request.
     * Returns no rules when the shop already has a locked account (request fields are ignored).
     */
    public static function rules(?Shop $shop, Request $request): array
    {
        if ($shop && $shop->hasLockedPayoutAccount()) {
            return [];
        }

        $method = $request->input('payout_method');

        $rules = [
            'payout_method' => 'required|in:'.implode(',', self::METHODS),
        ];

        if ($method === 'bank_transfer') {
            $rules['payout_bank_name'] = 'required|string|max:255';
            $rules['payout_account_holder'] = 'required|string|max:255';
            $rules['payout_account_number'] = 'required|string|max:255';
        }

        if (isset(self::MOBILE_PREFIXES[$method])) {
            $rules['payout_mobile'] = [
                'required',
                'string',
                'max:32',
                function ($attribute, $value, $fail) use ($method) {
                    if (self::normalizeMobile($method, (string) $value) === null) {
                        $fail(trans('packages.wallet.payout_mobile_invalid', [
                            'method' => trans('packages.wallet.payout_method_'.$method),
                            'prefixes' => implode('/', self::MOBILE_PREFIXES[$method]),
                        ]));
                    }
                },
            ];
        }

        return $rules;
    }

    /**
     * Resolve the account a withdrawal is paid to. Uses the locked account when present;
     * otherwise registers the account from the request and locks it on the shop.
     *
     * @return array{method: string, details: array<string, string>, instruction: string}
     */
    public static function resolveForWithdrawal(Shop $shop, Request $request): array
    {
        if (! $shop->hasLockedPayoutAccount()) {
            $method = (string) $request->input('payout_method');

            $shop->payout_method = $method;
            $shop->payout_details = self::detailsFromRequest($method, $request);
            $shop->payout_locked_at = now();
            $shop->pay_to = format_payout_instruction_text($method, $shop->payout_details);
            $shop->save();
        }

        return [
            'method' => (string) $shop->payout_method,
            'details' => (array) $shop->payout_details,
            'instruction' => format_payout_instruction_text((string) $shop->payout_method, (array) $shop->payout_details),
        ];
    }

    /**
     * Admin action: clear the locked account so the vendor can register a new one.
     */
    public static function reset(Shop $shop): void
    {
        $shop->payout_method = null;
        $shop->payout_details = null;
        $shop->payout_locked_at = null;
        $shop->pay_to = null;
        $shop->save();
    }

    /**
     * Summary for display/API: null when no account is registered yet.
     */
    public static function summary(Shop $shop): ?array
    {
        if (! $shop->hasLockedPayoutAccount()) {
            return null;
        }

        return [
            'method' => $shop->payout_method,
            'method_label' => trans('packages.wallet.payout_method_'.$shop->payout_method),
            'details' => $shop->payout_details,
            'instruction' => format_payout_instruction_text((string) $shop->payout_method, (array) $shop->payout_details),
            'locked_at' => optional($shop->payout_locked_at)->toIso8601String(),
        ];
    }

    /**
     * Normalize a mobile-money number to 258XXXXXXXXX, or null when it does not
     * belong to the selected network.
     */
    public static function normalizeMobile(string $method, string $raw): ?string
    {
        $prefixes = self::MOBILE_PREFIXES[$method] ?? null;

        if (! $prefixes) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $raw);

        if (preg_match('/^(?:258)?(\d{9})$/', $digits, $m) && in_array(substr($m[1], 0, 2), $prefixes, true)) {
            return '258'.$m[1];
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private static function detailsFromRequest(string $method, Request $request): array
    {
        if (isset(self::MOBILE_PREFIXES[$method])) {
            return [
                'mobile' => (string) self::normalizeMobile($method, (string) $request->input('payout_mobile')),
            ];
        }

        return [
            'bank_name' => trim((string) $request->input('payout_bank_name')),
            'account_holder' => trim((string) $request->input('payout_account_holder')),
            'account_number' => trim((string) $request->input('payout_account_number')),
        ];
    }
}
