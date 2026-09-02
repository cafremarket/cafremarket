<?php

namespace App\Http\Middleware;

use App\Services\Auth\JwtAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SyncJwtToSession
{
    /**
     * Restore authenticated users from JWT cookies when PHP sessions expire.
     */
    public function handle(Request $request, Closure $next)
    {
        $jwt = app(JwtAuthService::class);

        foreach (['customer', 'web', 'affiliate'] as $guard) {
            if (Auth::guard($guard)->check()) {
                continue;
            }

            $user = $jwt->resolveFromRequest($request, $guard);

            if ($user) {
                Auth::guard($guard)->login($user);
            }
        }

        return $next($request);
    }
}
