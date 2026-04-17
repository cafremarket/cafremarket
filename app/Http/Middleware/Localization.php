<?php

namespace App\Http\Middleware;

use App\Helpers\ListHelper;
use Closure;
use Illuminate\Http\Request;

class Localization
{
    /**
     * Handle an incoming request to set locale for api requests.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $localization = $request->header('Accept-Language');
        $available_languages = ListHelper::availableLocales()->pluck('code')->toArray();
        $localization = in_array($localization, $available_languages, true) ? $localization : config('system_settings.default_language');

        app()->setLocale($localization);

        return $next($request);
    }
}
