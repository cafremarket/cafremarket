<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">
  <!-- sidebar: style can be found in sidebar.less -->
  <section class="sidebar">
    <ul class="sidebar-menu">
      <li class="{{ Request::is('affiliate/dashboard*') ? 'active' : '' }}">
        <a href="{{ route('affiliate.dashboard') }}">
          <i class="fa fa-dashboard"></i> {{ trans('nav.dashboard') }}
        </a>
      </li>
      <li class="{{ Request::is('affiliate/link*') ? 'active' : '' }}">
        <a href="{{ route('affiliate.link.index') }}">
          <i class="fa fa-link"></i> {{ trans('packages.affiliate.affiliate_links') }}
        </a>
      </li>
      <li class="{{ Request::is('affiliate/wallet*') ? 'active' : '' }}">
        <a href="{{ route('affiliate.wallet') }}">
          <i class="fa fa-money"></i> {{ trans('packages.wallet.wallet') }}
        </a>
      </li>

      <li class="{{ Request::is('affiliate/commissions*') ? 'active' : '' }}">
        <a href="{{ route('affiliate.commissions') }}">
          <i class="fa fa-percent"></i> {{ trans('packages.affiliate.commission') }}
        </a>
      </li>
    </ul>
  </section> <!-- /.sidebar -->
</aside>
