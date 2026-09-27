{{-- Top navigation shared by every admin report page. Keeps the chosen period/shop when switching tabs. --}}
@include('admin.partials.reports.styles')

@php
  $reportQuery = request()->only(['from', 'to', 'shop_id']);
  $reportTabs = [
      ['route' => 'admin.report.overview', 'match' => 'admin.report.overview', 'icon' => 'fa-dashboard', 'label' => trans('reports.nav.overview')],
      ['route' => 'admin.report.commission', 'match' => 'admin.report.commission', 'icon' => 'fa-percent', 'label' => trans('reports.nav.commission')],
      ['route' => 'admin.sales.orders', 'match' => 'admin.sales.*', 'icon' => 'fa-shopping-cart', 'label' => trans('reports.nav.sales')],
      ['route' => 'admin.report.vendors', 'match' => 'admin.report.vendors', 'icon' => 'fa-building', 'label' => trans('reports.nav.vendors')],
      ['route' => 'admin.report.customers', 'match' => 'admin.report.customers', 'icon' => 'fa-users', 'label' => trans('reports.nav.customers')],
      ['route' => 'admin.report.refunds', 'match' => 'admin.report.refunds', 'icon' => 'fa-undo', 'label' => trans('reports.nav.refunds')],
  ];

  if (is_incevio_package_loaded('wallet') && Route::has('admin.wallet.payout.report') && Gate::allows('report', \Incevio\Package\Wallet\Models\Wallet::class)) {
      $reportTabs[] = ['route' => 'admin.wallet.payout.report', 'match' => 'admin.wallet.payout.report', 'icon' => 'fa-money', 'label' => trans('reports.nav.payouts'), 'plain' => true];
  }

  $reportTabs[] = ['route' => 'admin.kpi', 'match' => 'admin.kpi*', 'icon' => 'fa-line-chart', 'label' => trans('reports.nav.performance'), 'plain' => true];
@endphp

<nav class="report-hub-nav">
  <ul>
    @foreach ($reportTabs as $tab)
      <li class="{{ request()->routeIs($tab['match']) ? 'active' : '' }}">
        <a href="{{ route($tab['route'], empty($tab['plain']) ? $reportQuery : []) }}">
          <i class="fa {{ $tab['icon'] }}"></i>{{ $tab['label'] }}
        </a>
      </li>
    @endforeach
  </ul>
</nav>
