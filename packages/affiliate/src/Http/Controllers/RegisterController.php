<?php

namespace Incevio\Package\Affiliate\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\RegistersUsers;
use Incevio\Package\Affiliate\Models\Affiliate;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Display the registration form.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        return view('affiliate::frontend.register');
    }

    /**
     * Register a new affiliate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:affiliates,email',
            'username' => 'required|unique:affiliates,username',
            'password' => 'required|min:6',
        ]);

        $affiliate = Affiliate::create($validatedData);

        Auth::guard('affiliate')->login($affiliate);

        return redirect()->route('affiliate.dashboard')->with('success', trans('packages.affiliate.successfully_registered_as_affiliate'));
    }

    /**
     * Get the guard instance for the affiliate authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    protected function guard()
    {
        return Auth::guard('affiliate');
    }
}
