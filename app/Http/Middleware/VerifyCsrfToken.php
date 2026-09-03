<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'addToCart/*',
        'api/*',
        'customer/login/apple/callback',
        'ebay/callbacks/*',
        'paymentFailed/*',
        'payment/response/callback',
        'socialite/customer/apple/callback',
        '/build-twiml/*',
        'stripe/*',
        'payment/callback/*', // Use this common API for all payment webhook callback
        'payment/callback/*/failed', // Use this common API for all payment failed notification callback
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, \Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            // Recover gracefully on store auth forms instead of a raw 419 Page Expired.
            if ($this->shouldRecoverAuthForm($request)) {
                if ($request->hasSession()) {
                    $request->session()->regenerateToken();
                }

                $redirect = $this->authFormRedirectUrl($request);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => trans('messages.session_expired_retry'),
                        'redirect' => $redirect,
                    ], 419);
                }

                return redirect()
                    ->to($redirect)
                    ->withInput($request->except(['password', 'password_confirmation', '_token']))
                    ->with('error', trans('messages.session_expired_retry'));
            }

            throw $e;
        }
    }

    protected function shouldRecoverAuthForm($request): bool
    {
        return $request->isMethod('POST')
            && (
                $request->is('selling/register')
                || $request->is('selling/register/*')
                || $request->is('selling/login')
                || $request->is('register')
                || $request->is('register/*')
                || $request->is('login')
                || $request->is('admin/login')
                || $request->routeIs([
                    'selling.register.submit',
                    'selling.login',
                    'vendor.register',
                    'login',
                    'admin.login',
                ])
            );
    }

    protected function authFormRedirectUrl($request): string
    {
        if ($request->is('selling/register') || $request->is('selling/register/*') || $request->routeIs('selling.register.submit')) {
            return route('selling.register');
        }

        if ($request->is('selling/login') || $request->routeIs('selling.login')) {
            return route('selling.login');
        }

        if ($request->is('admin/login') || $request->routeIs('admin.login')) {
            return route('admin.login');
        }

        if ($request->is('register') || $request->is('register/*') || $request->routeIs('vendor.register')) {
            return route('vendor.register');
        }

        return route('login');
    }
}
