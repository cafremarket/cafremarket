<?php

namespace App\Http\Middleware;

use Closure;

class CheckIfBillingInfoRequired
{
    /**
     * Wallet billing does not require a saved card.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}
