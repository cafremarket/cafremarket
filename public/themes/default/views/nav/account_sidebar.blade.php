@php
  $customer = Auth::guard('customer')->user();
@endphp

<button type="button" class="sf-account-nav__toggle" id="sf-account-nav-toggle" aria-expanded="false" aria-controls="sf-account-nav">
  <span class="sf-account-nav__toggle-left">
    <i class="fas fa-bars" aria-hidden="true"></i>
    <span>@lang('theme.nav.my_account')</span>
  </span>
  <i class="fas fa-chevron-down sf-account-nav__toggle-chevron" aria-hidden="true"></i>
</button>

<aside class="sf-account-sidebar" aria-label="@lang('theme.nav.my_account')">
  <div class="sf-account-sidebar__profile">
    <img src="{{ get_avatar_src($customer, 'thumbnail') }}" alt="{{ $customer->getName() }}" class="sf-account-sidebar__avatar">
    <p class="sf-account-sidebar__name">{{ $customer->getName() }}</p>
    <p class="sf-account-sidebar__since">{{ trans('theme.member_since') }} {{ $customer->created_at->format('M Y') }}</p>
  </div>

  <nav>
    <ul class="sf-account-nav" id="sf-account-nav">
      <li class="sf-account-nav__section">{{ trans('theme.nav.dashboard') }}</li>
      <li class="{{ $tab == 'dashboard' ? 'active' : '' }}">
        <a href="{{ route('account', 'dashboard') }}">
          <span class="sf-account-nav__icon"><i class="fas fa-tachometer-alt" aria-hidden="true"></i></span>
          <span>@lang('theme.nav.dashboard')</span>
        </a>
      </li>

      <li class="sf-account-nav__section">{{ trans('theme.nav.my_orders') }}</li>
      <li class="{{ $tab == 'orders' ? 'active' : '' }}">
        <a href="{{ route('account', 'orders') }}">
          <span class="sf-account-nav__icon"><i class="fas fa-shopping-bag" aria-hidden="true"></i></span>
          <span>@lang('theme.nav.my_orders')</span>
        </a>
      </li>

      <li class="{{ $tab == 'wishlist' ? 'active' : '' }}">
        <a href="{{ route('account', 'wishlist') }}">
          <span class="sf-account-nav__icon"><i class="fas fa-heart" aria-hidden="true"></i></span>
          <span>@lang('theme.nav.my_wishlist')</span>
        </a>
      </li>

      <li class="{{ $tab == 'disputes' ? 'active' : '' }}">
        <a href="{{ route('account', 'disputes') }}">
          <span class="sf-account-nav__icon"><i class="fas fa-undo-alt" aria-hidden="true"></i></span>
          <span>@lang('theme.nav.refunds_disputes')</span>
        </a>
      </li>

      <li class="{{ $tab == 'coupons' ? 'active' : '' }}">
        <a href="{{ route('account', 'coupons') }}">
          <span class="sf-account-nav__icon"><i class="fas fa-tags" aria-hidden="true"></i></span>
          <span>@lang('theme.nav.my_coupons')</span>
        </a>
      </li>

      <li class="sf-account-nav__section">{{ trans('theme.nav.my_account') }}</li>

      @if (customer_has_wallet())
        <li class="{{ $tab == 'wallet' || $tab == 'deposit' ? 'active' : '' }}">
          <a href="{{ route('customer.account.wallet') }}">
            <span class="sf-account-nav__icon"><i class="fas fa-wallet" aria-hidden="true"></i></span>
            <span>@lang('packages.wallet.my_wallet')</span>
          </a>
        </li>
      @endif

      <li class="{{ $tab == 'messages' || $tab == 'message' ? 'active' : '' }}">
        <a href="{{ route('account', 'messages') }}">
          <span class="sf-account-nav__icon"><i class="fas fa-envelope" aria-hidden="true"></i></span>
          <span>@lang('theme.my_messages')</span>
        </a>
      </li>

      @if (is_incevio_package_loaded('eventy'))
        <li class="{{ $tab == 'events' ? 'active' : '' }}">
          <a href="{{ route('account', 'events') }}">
            <span class="sf-account-nav__icon"><i class="fas fa-calendar-alt" aria-hidden="true"></i></span>
            <span>@lang('packages.eventy.my_events')</span>
          </a>
        </li>
      @endif

      <li class="{{ $tab == 'account' ? 'active' : '' }}">
        <a href="{{ route('account', 'account') }}">
          <span class="sf-account-nav__icon"><i class="fas fa-user-cog" aria-hidden="true"></i></span>
          <span>@lang('theme.nav.my_account')</span>
        </a>
      </li>
    </ul>
  </nav>

  <div class="sf-account-sidebar__footer">
    <a href="{{ route('customer.logout') }}" class="sf-account-sidebar__logout">
      <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
      <span>@lang('theme.logout')</span>
    </a>
  </div>
</aside>
