<?php

namespace App\Http\Controllers\Selling;

use App\Http\Controllers\Auth\LoginController as AuthLoginController;

class LoginController extends AuthLoginController
{
    /**
     * Show seller login form (selling theme).
     */
    public function showLoginForm()
    {
        return view('login');
    }
}
