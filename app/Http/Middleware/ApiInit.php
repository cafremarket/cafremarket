<?php

namespace App\Http\Middleware;

use Closure;

class ApiInit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        setSystemConfig();

        return $next($request);
    }
}
