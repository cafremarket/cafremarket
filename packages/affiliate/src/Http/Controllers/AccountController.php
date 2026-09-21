<?php

namespace Incevio\Package\Affiliate\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Incevio\Package\Affiliate\Models\Affiliate;

class AccountController extends Controller
{
    /**
     * Display the profile of the authenticated affiliate.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function profile()
    {
        $affiliate = Auth::guard('affiliate')->user();

        return view('affiliate::frontend.profile', compact('affiliate'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Affiliate $affiliate)
    {
        $affiliate->update($request->all());

        return redirect()->back()->with('success', trans('packages.affiliate.affiliate_updated'));
    }

    /**
     * Display the change password form for the specified affiliate.
     *
     * @param Affiliate $affiliate The affiliate instance.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View The view instance.
     */
    public function showChangePasswordForm(Affiliate $affiliate)
    {
        if (Auth::guard('affiliate')->check()) {
            $affiliate = Auth::guard('affiliate')->user();
        }

        return view('affiliate::frontend._password_modal', compact('affiliate'));
    }

    /**
     * Update the password for the affiliate.
     *
     * @param Affiliate $affiliate The affiliate instance.
     * @param Request $request The HTTP request instance.
     * @return \Illuminate\Http\RedirectResponse The redirect response.
     */
    public function updatePassword(Affiliate $affiliate, Request $request)
    {
        $this->validate($request, [
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
        ]);

        $affiliate->update($request->all());

        return back()->with('success', trans('packages.affiliate.notification_password_updated'));
    }
}
