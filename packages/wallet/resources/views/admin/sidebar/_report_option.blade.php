@can('report', \Incevio\Package\Wallet\Models\Wallet::class)
  <li class="{{ Request::is('admin/report/payout*') ? 'active' : '' }}">
    <a href="{{ route('admin.wallet.payout.report') }}">
      <i class="fa fa-angle-double-right"></i> {{ trans('nav.payout') }}
    </a>
  </li>
@endcan
