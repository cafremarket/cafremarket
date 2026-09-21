<?php

namespace Incevio\Package\Affiliate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Auth\JwtAuthService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Incevio\Package\Affiliate\Models\Affiliate;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/affiliate/dashboard';

    public function showLoginForm()
    {
        return view('affiliate::frontend.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required' => trans('packages.affiliate.email_required'),
            'email.email' => trans('packages.affiliate.email_invalid'),
            'password.required' => trans('packages.affiliate.password_required'),
        ]);

        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            return $this->sendLoginResponse($request);
        }

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Failed login — show a clear message on the affiliate login form.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $message = trans('auth.failed');

        return redirect()->route('affiliate.login.form')
            ->withInput($request->only($this->username()))
            ->withErrors([
                $this->username() => $message,
            ])
            ->with('error', $message);
    }

    protected function guard()
    {
        return Auth::guard('affiliate');
    }

    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        /** @var Affiliate $affiliate */
        $affiliate = $this->guard()->user();
        $jwt = app(JwtAuthService::class)->issue($affiliate, 'affiliate');
        $cookie = app(JwtAuthService::class)->makeCookie(
            'affiliate',
            $jwt,
            $request->filled('remember')
        );

        return redirect()->route('affiliate.dashboard')
            ->with('success', trans('packages.affiliate.affiliate_log_in_successful'))
            ->withCookie($cookie);
    }

    protected function attemptLogin(Request $request)
    {
        return $this->guard()->attempt(
            $request->only($this->username(), 'password'),
            $request->filled('remember')
        );
    }

    public function logout(Request $request)
    {
        $affiliate = $this->guard()->user();

        if ($affiliate instanceof Affiliate) {
            app(JwtAuthService::class)->invalidate($affiliate, 'affiliate');
        }

        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('affiliate.login.form')
            ->withCookie(app(JwtAuthService::class)->forgetCookie('affiliate'));
    }
}
