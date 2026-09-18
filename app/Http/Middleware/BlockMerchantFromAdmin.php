<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class BlockMerchantFromAdmin
{
    /**
     * Exact admin path → merchant path remaps.
     *
     * @var array<string, string>
     */
    protected array $pathMap = [
        'admin/setting/verify' => 'merchant/verify',
        'admin/dashboard' => 'merchant/dashboard',
        'admin/seller/shop' => 'merchant/setting/general',
        'admin/seller/merchant' => 'merchant/dashboard',
    ];

    /**
     * Admin path prefixes that are platform-only. Merchants hitting these
     * (via rewrite, bookmark, or back-button) land on a sensible shop page.
     *
     * @var array<string, string>
     */
    protected array $prefixMap = [
        'admin/seller' => 'merchant/setting/general',
        'admin/report' => 'merchant/dashboard',
        'admin/packages' => 'merchant/dashboard',
        'admin/package' => 'merchant/dashboard',
        'admin/admin' => 'merchant/dashboard',
    ];

    /**
     * Keep merchants out of /admin — send them to the merchant panel instead.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->isFromMerchant()) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        // Impersonation entry/exit must stay on /admin (never rewrite to /merchant/…).
        if (
            $path === 'admin/secretLogout'
            || $path === 'admin/secretLogin'
            || str_starts_with($path, 'admin/secretLogin/')
        ) {
            return $next($request);
        }

        if (isset($this->pathMap[$path])) {
            return redirect()->to('/'.$this->pathMap[$path]);
        }

        foreach ($this->prefixMap as $prefix => $target) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return redirect()->to('/'.$target);
            }
        }

        if ($path === 'admin' || str_starts_with($path, 'admin/')) {
            $suffix = ltrim(substr($path, strlen('admin')), '/');
            $target = $suffix === '' ? 'merchant/dashboard' : 'merchant/'.$suffix;

            // Never send merchants to a path the merchant panel does not register
            // (e.g. admin/seller/shop → merchant/seller/shop would 404 in nginx).
            if ($suffix !== '' && ! $this->merchantRouteExists($target)) {
                return redirect()->to('/merchant/dashboard');
            }

            return redirect()->to('/'.$target);
        }

        return $next($request);
    }

    /**
     * True when Laravel has a matching GET route for the merchant-panel path.
     */
    protected function merchantRouteExists(string $path): bool
    {
        try {
            Route::getRoutes()->match(Request::create('/'.$path, 'GET'));

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
