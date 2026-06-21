@include('admin.partials.reports.styles')

<div class="report-sales-nav">
  <ul class="nav nav-pills">
    <li class="{{ request()->routeIs('admin.sales.orders') ? 'active' : '' }}">
      <a href="{{ route('admin.sales.orders') }}">
        <i class="fa fa-shopping-cart"></i> {{ trans('app.orders') }}
      </a>
    </li>
    <li class="{{ request()->routeIs('admin.sales.payments') ? 'active' : '' }}">
      <a href="{{ route('admin.sales.payments') }}">
        <i class="fa fa-credit-card"></i> {{ trans('app.payments') }}
      </a>
    </li>
    <li class="{{ request()->routeIs('admin.sales.products') ? 'active' : '' }}">
      <a href="{{ route('admin.sales.products') }}">
        <i class="fa fa-cube"></i> {{ trans('app.products') }}
      </a>
    </li>
  </ul>
</div>
