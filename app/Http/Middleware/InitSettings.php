<?php

namespace App\Http\Middleware;

use App\Helpers\ListHelper;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class InitSettings
{
    public function handle($request, Closure $next)
    {
        setSystemConfig();
        View::addNamespace('theme', theme_views_path());

        if (! Auth::guard('web')->check()) {
            return $next($request);
        }

        if ($request->session()->has('impersonated')) {
            Auth::onceUsingId($request->session()->get('impersonated'));
        }

        if (! ($request->is('admin/*') || $request->is('account/*'))) {
            return $next($request);
        }

        $user = Auth::guard('web')->user();

        if ($user->merchantId()) {
            setShopConfig($user->merchantId());
        }

        $permissions = Cache::remember('permissions_'.$user->id, system_cache_remember_for(), function () {
            return ListHelper::authorizations();
        });

        $permissions = isset($extra_permissions)
            ? array_merge($extra_permissions, $permissions)
            : $permissions;

        config()->set('permissions', $permissions);

        if ($user->isSuperAdmin()) {
            $slugs = Cache::remember('slugs', system_cache_remember_for(), function () {
                return ListHelper::slugsWithModulAccess();
            });
            config()->set('authSlugs', $slugs);
        }

        return $next($request);
    }
}
