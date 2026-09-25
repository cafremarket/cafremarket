<?php

namespace App\Http\Middleware;

use Closure;

class IsSubscriptionEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! is_subscription_enabled()) {
            abort(403, trans('messages.subscription_module_disabled'));
        }

        return $next($request);
    }
}
