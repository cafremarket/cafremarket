<?php

namespace App\Http\Controllers\Selling;

use App\Helpers\ListHelper;
use App\Http\Controllers\Auth\RegisterController as AuthRegisterController;

class RegisterController extends AuthRegisterController
{
    /**
     * Show seller registration form (selling theme).
     */
    public function showRegistrationForm($plan = null)
    {
        return view('register', [
            'plan' => $plan,
            'plans' => ListHelper::plans(),
        ]);
    }
}
