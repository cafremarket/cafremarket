<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockMerchantFromAdmin
{
    protected array $pathMap = [
        'admin/setting/verify' => 'merchant/verify',
        'admin/dashboard' => 'merchant/dashboard',
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

        if (isset($this->pathMap[$path])) {
            return redirect()->to('/'.$this->pathMap[$path]);
        }

        if ($path === 'admin' || str_starts_with($path, 'admin/')) {
            $suffix = substr($path, strlen('admin'));
            $suffix = ltrim($suffix, '/');
            $target = $suffix === '' ? 'merchant/dashboard' : 'merchant/'.$suffix;

            return redirect()->to('/'.$target);
        }

        return $next($request);
    }
}
