<aside class="mp-sidebar" id="mp-sidebar">
  <div class="mp-sidebar__brand">
    @if (system_has_custom_logo())
      <img src="{{ get_logo_url('system', 'logo') }}" alt="{{ get_platform_title() }}">
    @endif
    <span>{{ trans('packages.affiliate.affiliate') }}</span>
  </div>

  <nav class="mp-sidebar__nav" id="mp-sidebar-nav">
    <a href="{{ route('affiliate.dashboard') }}" class="mp-sidebar__link {{ Request::is('affiliate/dashboard*') ? 'is-active' : '' }}">
      <i class="fa fa-dashboard"></i>
      <span>{{ trans('nav.dashboard') }}</span>
    </a>

    <a href="{{ route('affiliate.link.index') }}" class="mp-sidebar__link {{ Request::is('affiliate/link*') ? 'is-active' : '' }}">
      <i class="fa fa-link"></i>
      <span>{{ trans('packages.affiliate.affiliate_links') }}</span>
    </a>

    <a href="{{ route('affiliate.wallet') }}" class="mp-sidebar__link {{ Request::is('affiliate/wallet*') ? 'is-active' : '' }}">
      <i class="fa fa-money"></i>
      <span>{{ trans('packages.wallet.wallet') }}</span>
    </a>

    <a href="{{ route('affiliate.commissions') }}" class="mp-sidebar__link {{ Request::is('affiliate/commissions*') ? 'is-active' : '' }}">
      <i class="fa fa-percent"></i>
      <span>{{ trans('packages.affiliate.commission') }}</span>
    </a>

    <a href="{{ route('affiliate.profile') }}" class="mp-sidebar__link {{ Request::is('affiliate/profile*') ? 'is-active' : '' }}">
      <i class="fa fa-user"></i>
      <span>{{ trans('app.profile') }}</span>
    </a>
  </nav>
</aside>
