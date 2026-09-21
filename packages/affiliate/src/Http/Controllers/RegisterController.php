<?php

namespace Incevio\Package\Affiliate\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Incevio\Package\Affiliate\Models\Affiliate;

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
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('affiliates', 'email')->whereNull('deleted_at'),
            ],
            'password' => 'required|string|min:6|confirmed',
            'agree' => 'accepted',
        ], [
            'name.required' => trans('packages.affiliate.name_required'),
            'email.required' => trans('packages.affiliate.email_required'),
            'email.email' => trans('packages.affiliate.email_invalid'),
            'email.unique' => trans('packages.affiliate.email_already_registered'),
            'password.required' => trans('packages.affiliate.password_required'),
            'password.min' => trans('packages.affiliate.password_min'),
            'password.confirmed' => trans('packages.affiliate.password_confirmation_mismatch'),
            'agree.accepted' => trans('packages.affiliate.terms_required'),
        ]);

        try {
            Affiliate::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => $validatedData['password'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            if (is_unique_constraint_violation($e)) {
                throw ValidationException::withMessages([
                    'email' => [trans('packages.affiliate.email_already_registered')],
                ]);
            }

            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', trans('packages.affiliate.registration_failed'));
        }

        return redirect()->route('affiliate.login.form')
            ->with('success', trans('packages.affiliate.successfully_registered_as_affiliate'));
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
