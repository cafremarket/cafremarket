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

            // Cookie only: a token from the URL or a header would let a link log the browser into
            // someone else's account (and leak tokens into logs and Referer headers).
            $cookie = config("jwt.guards.{$guard}.cookie");
            $user = $cookie ? $jwt->resolve($request->cookie($cookie), $guard) : null;

            if ($user) {
                Auth::guard($guard)->login($user);
            }
        }

        return $next($request);
    }
}
