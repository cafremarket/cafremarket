<?php

namespace App\Http\Middleware;

use App\Services\Auth\VerificationHealth;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Lets unverified accounts browse but stops them placing orders, reviewing,
 * messaging, opening disputes or submitting a shop for approval.
 *
 * Usage: 'verifiedEmail:api' or 'verifiedEmail:customer,api'. Guests pass
 * through; the route's own auth rules decide what they can do. While mail is
 * down nothing is blocked, since the code could never arrive.
 */
class RequireVerifiedEmail
{
    public function handle($request, Closure $next, ...$guards)
    {
        try {
            if (! VerificationHealth::emailVerificationEnforced()) {
                return $next($request);
            }

            $account = null;

            foreach ($guards ?: [null] as $guard) {
                if ($account = Auth::guard($guard)->user()) {
                    break;
                }
            }

            $blocked = $account && method_exists($account, 'hasVerifiedEmail') && ! $account->hasVerifiedEmail();
        } catch (\Throwable $e) {
            VerificationHealth::logIssue('middleware', 'Verification check skipped: '.$e->getMessage());
            $blocked = false;
        }

        if (! $blocked) {
            return $next($request);
        }

        $message = trans('auth.email_verification_required', ['email' => $account->email]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
                'error_code' => 'email_not_verified',
                'email' => $account->email,
            ], 403);
        }

        return redirect()->back()->with('error', $message);
    }
}
