<?php

namespace Incevio\Package\Affiliate\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
  /**
   * Display the affiliate dashboard.
   *
   * @return \Illuminate\View\View
   */
  public function index(Request $request)
  {
    $affiliate = Auth::guard('affiliate')->user();

    $new_visitors = $affiliate->affiliateLinks()->sum('visitor_count');
    $todays_commission = $affiliate->commissions()->whereDate('created_at', today())->sum('total_commission');
    $todays_order_count = $affiliate->commissions()->whereDate('created_at', today())->distinct('order_id')->count('order_id');

    $last_thirty_days_order_count = $affiliate->commissions()
      ->where('created_at', '>=', today()->subDays(30))
      ->distinct('order_id')
      ->count('order_id');

    $last_thirty_days_commission = $affiliate->commissions()
      ->where('created_at', '>=', today()->subDays(30))
      ->sum('total_commission');

    $new_product_sold = 0;
    foreach ($affiliate->commissions()->whereDate('created_at', today())->get() as $commission) {
      $product_sold = $commission->order->items()
        ->where('inventory_id', $commission->inventory_id)
        ->value('quantity');
      $new_product_sold += $product_sold;
    }

    $last_thirty_days_product_sold = 0;
    foreach ($affiliate->commissions()->where('created_at', '>=', today()->subDays(30))->get() as $commission) {
      $product_sold = $commission->order->items()
        ->where('inventory_id', $commission->inventory_id)
        ->value('quantity');
      $last_thirty_days_product_sold += $product_sold;
    }

    $top_links_by_commission = $affiliate->commissions()
      ->whereNotNull('affiliate_link_id')
      ->groupBy('affiliate_link_id')
      ->selectRaw('affiliate_link_id, sum(total_commission) as total_commission, order_id')
      ->orderBy('total_commission', 'desc')
      ->take(10)
      ->get();

    $top_links_by_visitors = $affiliate->affiliateLinks()->select('visitor_count', 'inventory_id', 'slug')
      ->orderBy('visitor_count', 'desc')
      ->take(10)
      ->get();

    return view('affiliate::backend.dashboard.index', compact('affiliate', 'new_visitors', 'todays_order_count', 'last_thirty_days_order_count', 'todays_commission', 'last_thirty_days_commission', 'new_product_sold', 'last_thirty_days_product_sold', 'top_links_by_visitors', 'top_links_by_commission'));
  }

  /**
   * Display the wallet page.
   *
   * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
   */
  public function wallet()
  {
    $wallet = Auth::guard('affiliate')->user();
    $pending_commissions = $wallet->commissions()->unpaid()->sum('total_commission');

    return view('affiliate::frontend.wallet_index', compact('wallet', 'pending_commissions'));
  }

  public function showCommissions()
  {
    $affiliate = Auth::guard('affiliate')->user();
    $commissions = $affiliate->commissions()->get();

    return view('affiliate::frontend.commissions', compact('commissions'));
  }
}
