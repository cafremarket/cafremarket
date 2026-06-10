<?php

namespace App\Http\Middleware;

use Closure;

class CheckIfBillingInfoRequired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (
            ! requires_stripe_card_for_subscription() ||
            $request->user()->isFromPlatform() ||
            $request->user()->hasBillingInfo() ||
            ($request->user()->shop->stripe_id && $request->user()->shop->pm_last_four)
        ) {
            return $next($request);
        }

        return $request->ajax() || $request->wantsJson() ?
            response(trans('messages.no_card_added'), 402)
            : redirect()->route('admin.account.billing')->with('error', trans('messages.no_card_added'));
    }
}
