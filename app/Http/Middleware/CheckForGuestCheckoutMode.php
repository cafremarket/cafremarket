<?php

namespace App\Http\Middleware;

use Closure;

class CheckForGuestCheckoutMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! allow_checkout()) {
            if (is_panel_user_on_storefront()) {
                return redirect()->route('cart.index')
                    ->with('warning', panel_user_storefront_message());
            }

            return redirect()->route('homepage', ['login' => 1])
                ->with('error', trans('theme.notify.please_login_to_checkout'));
        }

        return $next($request);
    }
}
