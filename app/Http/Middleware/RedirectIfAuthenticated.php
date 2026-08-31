<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            switch ($guard) {
                case 'customer':
                    if (Auth::guard($guard)->check()) {
                        return redirect()->route('account', 'dashboard');
                    }
                    break;
            }

            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                if ($user && $user->isFromMerchant()) {
                    return redirect(RouteServiceProvider::MERCHANT_DASHBOARD);
                }

                return redirect(RouteServiceProvider::DASHBOARD);
            }
        }

        return $next($request);
    }
}
