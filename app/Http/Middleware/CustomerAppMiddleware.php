<?php

namespace App\Http\Middleware;

use App\Jobs\UpdateVisitorTable;
use Closure;
use Illuminate\Http\Request;

class CustomerAppMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // update the visitor table for state
        if (config('report.collect_visitor_data')) {
            $ip = get_visitor_IP();

            UpdateVisitorTable::dispatch($ip);
        }

        return $next($request);
    }
}
