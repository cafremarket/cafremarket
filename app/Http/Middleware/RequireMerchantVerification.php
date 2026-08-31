<?php

namespace App\Http\Middleware;

use Closure;

class RequireMerchantVerification
{
    /**
     * Keep merchants on verification until the store is approved by admin.
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->isFromMerchant()) {
            return $next($request);
        }

        if ($request->routeIs(
            'merchant.verify',
            'merchant.verify.submit',
            'merchant.verify.location',
            'merchant.verify.phone',
            'merchant.verify.documents.store',
            'merchant.verify.documents.replace',
            'merchant.verify.documents.delete',
            'merchant.account.billing',
            'merchant.account.billing.*',
            'merchant.switchToCustomer',
            'merchant.createCustomer',
            'logout'
        )) {
            return $next($request);
        }

        $shop = $user->shop;
        $config = $shop?->config;

        if (! $shop || ! $config) {
            return $next($request);
        }

        if ($shop->isVerified()) {
            return $next($request);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response(
                $config->pending_verification
                    ? trans('messages.verification_pending_until_approved')
                    : ($config->wasVerificationRejected()
                        ? trans('messages.verification_rejected_reapply_notice')
                        : trans('messages.complete_store_verification')),
                403
            );
        }

        return redirect()
            ->route('merchant.verify')
            ->with(
                'info',
                $config->pending_verification
                    ? trans('messages.verification_pending_until_approved')
                    : ($config->wasVerificationRejected()
                        ? trans('messages.verification_rejected_reapply_notice')
                        : trans('messages.complete_store_verification'))
            );
    }
}
