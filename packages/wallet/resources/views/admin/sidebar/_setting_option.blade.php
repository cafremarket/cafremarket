@can('setting', \Incevio\Package\Wallet\Models\Wallet::class)
  <li class="{{ Request::is('admin/setting/wallet*') ? 'active' : '' }}">
    <a href="{{ url('admin/setting/wallet') }}">
      <i class="fa fa-angle-double-right"></i> {{ trans('packages.wallet.wallet_settings') }}
    </a>
  </li>
@endcan