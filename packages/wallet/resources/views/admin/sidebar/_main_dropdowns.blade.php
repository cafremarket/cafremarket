@if (Auth::user()->isAdmin())
  <li class="treeview {{ Request::is('admin/payouts*') || Request::is('admin/payout*') || Request::is('admin/rewards*') || Request::is('admin/wallet/bulkupload/*') || Request::is('admin/affiliate/commissions*') ? 'active' : '' }}">
    <a href="javascript:void(0)">
      <i class="fa fa-money"></i>
      <span>{{ trans('packages.wallet.wallet') }}</span>
      @include('partials._addon_badge')
      <i class="fa fa-angle-left pull-right"></i>
    </a>

    <ul class="treeview-menu">
      @can('payout', \Incevio\Package\Wallet\Models\Wallet::class)
        <li class="{{ Request::is('admin/rewards*') ? 'active' : '' }}">
          <a href="{{ url('admin/rewards') }}">
          <i class="fa fa-angle-double-right"></i> {{ trans('packages.wallet.credit_rewards') }}
          </a>
        </li>
      @endcan

      @if (is_incevio_package_loaded('affiliate'))
        <li class="{{ Request::is('admin/affiliate/commissions*') ? 'active' : '' }}">
          <a href="{{ url('admin/affiliate/commissions') }}">
          <i class="fa fa-angle-double-right"></i> {{ trans('packages.affiliate.affiliate_commission') }}
          </a>
        </li>
      @endif

      <li class="{{ Request::is('admin/payouts*') ? 'active' : '' }}">
        <a href="{{ url('admin/payouts') }}">
        <i class="fa fa-angle-double-right"></i> {{ trans('packages.wallet.payouts') }}
        </a>
      </li>

      @can('payout', \Incevio\Package\Wallet\Models\Wallet::class)
        <li class="{{ Request::is('admin/payout/requests*') ? 'active' : '' }}">
          <a href="{{ url('admin/payout/requests') }}">
          <i class="fa fa-angle-double-right"></i> {{ trans('packages.wallet.payout_requests') }}
          </a>
        </li>

        <li class="{{ Request::is('admin/wallet/bulkupload/*') ? 'active' : '' }}">
          <a href="{{ route('admin.wallet.bulkupload.index') }}">
          <i class="fa fa-angle-double-right"></i> {{ trans('packages.wallet.wallet_bulk_upload') }}
          </a>
        </li>
      @endcan
    </ul>
  </li>
@endif

@if (Auth::user()->isMerchant())
  <li class="{{ Request::is('admin/wallet*') ? 'active' : '' }}">
    <a href="{{ route('merchant.wallet') }}">
      <i class="fa fa-money"></i> <span>{{ trans('packages.wallet.wallet') }}</span>
      @include('partials._addon_badge')
    </a>
  </li>
@endif