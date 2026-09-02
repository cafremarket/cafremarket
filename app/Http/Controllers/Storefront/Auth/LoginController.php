<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\SocialiteBaseController;
use App\Models\Customer;
use App\Services\Hyperlocal\BuyerLocationService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class LoginController extends SocialiteBaseController
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     */
    protected function redirectTo(): string
    {
        return route('homepage', absolute: false);
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest:customer')->except('logout');
        $this->middleware('guest:web')->except('logout');
        $this->middleware('customerCanRegister')->except('logout');
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        $phone = request()->input('phone');

        if (is_incevio_package_loaded('otp-login') && ! is_null($phone)) {
            return 'phone';
        }

        return 'email';
    }

    /**
     * Show the application's login form — customer login uses the storefront popup only.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function showLoginForm()
    {
        Session::put('url.intended', URL::previous());

        return redirect()->route('homepage', ['login' => 1]);
    }

    /**
     * Handle a login request to the application.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        if (is_incevio_package_loaded('otp-login') && $request->has('phone')) {
            $this->validate($request, [
                $this->username() => 'required',
            ]);

            if (! Customer::where('phone', $request['phone'])->exists()) {
                return redirect()->route('homepage', ['login' => 1])
                    ->withErrors([trans('packages.otp-login.not_registered')]);
            }

            try {
                send_otp_code($request['phone'], 'customer.login');
            } catch (\Exception $e) {
                return redirect()->route('homepage', ['login' => 1])
                    ->withErrors([trans('packages.otp-login.phone_session_expired')]);
            }

            return redirect()->route('phoneverification.notice')
                ->with(['phone_number' => $request['phone']]);
        }

        $this->validate($request, [
            $this->username($request) => 'required|string',
            'password' => 'required|string',
        ]);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        // if successful, then redirect to their intended location
        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        return Auth::guard('customer')
            ->attempt(
                $request->only($this->username($request), 'password'),
                $request->filled('remember')
            );
    }

    /**
     * After successful login, use saved default address as delivery location when available.
     */
    protected function authenticated(Request $request, $user)
    {
        $buyerLocation = app(BuyerLocationService::class);

        if ($user->addresses()->count() === 0) {
            session()->forget([
                'buyer_latitude',
                'buyer_longitude',
                'buyer_address_text',
            ]);
        }

        $buyerLocation->ensureDeliveryLocation($user);
    }

    /**
     * Failed login — return to storefront with login popup open.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        return redirect()->route('homepage', ['login' => 1])
            ->withInput($request->only($this->username($request)))
            ->withErrors([
                $this->username($request) => trans('auth.failed'),
            ]);
    }

    /**
     * Log the user out of the application.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $locale = Session::get('locale');

        $currency = Session::get('currency');

        Auth::guard('customer')->logout();

        $request->session()->invalidate();

        $request->session()->put('locale', $locale);

        $request->session()->put('currency', $currency);

        return redirect()->to('/')
            ->with('success', trans('theme.notify.logged_out_successfully'));
    }
}
