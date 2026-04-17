<?php

namespace App\Http\Middleware;

use Closure;

class AllowOnlyAjaxRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $request->ajax()) {
            return response('', 405);
        }

        return $next($request);
    }
}
