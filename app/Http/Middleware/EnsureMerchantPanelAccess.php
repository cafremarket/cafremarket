<?php

namespace App\Http\Middleware;

use App\Helpers\ListHelper;
use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EnsureMerchantPanelAccess
{
    /**
     * Only merchant users (owner or staff) may access the merchant panel.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('selling.login');
        }

        if ($user->isFromMerchant()) {
            $this->loadMerchantContext($user);

            return $next($request);
        }

        if ($user->isFromPlatform()) {
            return redirect(RouteServiceProvider::DASHBOARD);
        }

        abort(403);
    }

    /**
     * InitSettings only loads permissions for /admin/* — mirror that for merchant panel.
     */
    private function loadMerchantContext($user): void
    {
        if ($user->merchantId()) {
            setShopConfig($user->merchantId());
        }

        if (config('permissions') === null) {
            $permissions = Cache::remember(
                'permissions_'.$user->id,
                system_cache_remember_for(),
                fn () => ListHelper::authorizations()
            );

            config()->set('permissions', $permissions ?? []);
        }
    }
}
