<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Events\Customer\PasswordUpdated;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Providers\RouteServiceProvider;
use App\Services\Auth\CustomerJwtService;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::CUSTOMER_LOGIN;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest:customer');
        $this->middleware('customerCanRegister');
    }

    protected function guard()
    {
        return Auth::guard('customer');
    }

    protected function broker()
    {
        return Password::broker('customers');
    }

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('theme::auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * Reset the given customer's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $customer
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($customer, $password)
    {
        $customer->password = $password;

        $customer->setRememberToken(Str::random(60));

        $customer->save();

        event(new PasswordUpdated($customer));

        $this->guard('customer')->login($customer);
    }

    /**
     * Get the response for a successful password reset.
     *
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendResetResponse(Request $request, $response)
    {
        $customer = $this->guard()->user();
        $redirect = redirect($this->redirectPath())->with('status', trans($response));

        if ($customer instanceof Customer) {
            $jwt = app(CustomerJwtService::class)->issue($customer);

            return $redirect->withCookie(app(CustomerJwtService::class)->makeCookie($jwt));
        }

        return $redirect;
    }
}
