<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;

class DashboardController extends AdminDashboardController
{
    public function index()
    {
        $shop = Shop::with('activities.causer')->find(Auth::guard('web')->user()->shop_id);

        return view('merchant.dashboard.index', compact('shop'));
    }
}
