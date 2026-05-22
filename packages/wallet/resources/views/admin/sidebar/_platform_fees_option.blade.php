@can('setting', \Incevio\Package\Wallet\Models\Wallet::class)
  <li class="{{ Request::is('admin/setting/platform-fees*') ? 'active' : '' }}">
    <a href="{{ route('admin.wallet.platform_fees') }}">
      <i class="fa fa-angle-double-right"></i> {{ trans('packages.wallet.platform_fees_menu') }}
    </a>
  </li>
@endcan
