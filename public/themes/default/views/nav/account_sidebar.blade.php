@php
  $customer = Auth::guard('customer')->user();
@endphp

<button type="button" class="sf-account-nav__toggle" id="sf-account-nav-toggle" aria-expanded="false">
  <i class="fas fa-bars"></i> @lang('theme.nav.my_account')
</button>

<aside class="sf-account-sidebar">
  <div class="sf-account-sidebar__profile">
    <img src="{{ get_avatar_src($customer, 'thumbnail') }}" alt="{{ $customer->getName() }}" class="sf-account-sidebar__avatar">
    <p class="sf-account-sidebar__name">{{ $customer->getName() }}</p>
    <p class="sf-account-sidebar__since">{{ trans('theme.member_since') }} {{ $customer->created_at->format('M Y') }}</p>
  </div>

  <ul class="sf-account-nav" id="sf-account-nav">
    <li class="{{ $tab == 'dashboard' ? 'active' : '' }}">
      <a href="{{ route('account', 'dashboard') }}">
        <i class="fas fa-tachometer-alt"></i>
        <span>@lang('theme.nav.dashboard')</span>
      </a>
    </li>

    @if (customer_has_wallet())
      <li class="{{ $tab == 'wallet' || $tab == 'deposit' ? 'active' : '' }}">
        <a href="{{ route('customer.account.wallet') }}">
          <i class="fas fa-wallet"></i>
          <span>@lang('packages.wallet.my_wallet')</span>
        </a>
      </li>
    @endif

    <li class="{{ $tab == 'messages' || $tab == 'message' ? 'active' : '' }}">
      <a href="{{ route('account', 'messages') }}">
        <i class="fas fa-envelope"></i>
        <span>@lang('theme.my_messages')</span>
      </a>
    </li>

    <li class="{{ $tab == 'orders' ? 'active' : '' }}">
      <a href="{{ route('account', 'orders') }}">
        <i class="fas fa-shopping-bag"></i>
        <span>@lang('theme.nav.my_orders')</span>
      </a>
    </li>

    <li class="{{ $tab == 'wishlist' ? 'active' : '' }}">
      <a href="{{ route('account', 'wishlist') }}">
        <i class="fas fa-heart"></i>
        <span>@lang('theme.nav.my_wishlist')</span>
      </a>
    </li>

    <li class="{{ $tab == 'disputes' ? 'active' : '' }}">
      <a href="{{ route('account', 'disputes') }}">
        <i class="fas fa-undo-alt"></i>
        <span>@lang('theme.nav.refunds_disputes')</span>
      </a>
    </li>

    <li class="{{ $tab == 'coupons' ? 'active' : '' }}">
      <a href="{{ route('account', 'coupons') }}">
        <i class="fas fa-tags"></i>
        <span>@lang('theme.nav.my_coupons')</span>
      </a>
    </li>

    @if (is_incevio_package_loaded('eventy'))
      <li class="{{ $tab == 'events' ? 'active' : '' }}">
        <a href="{{ route('account', 'events') }}">
          <i class="fas fa-calendar-alt"></i>
          <span>@lang('packages.eventy.my_events')</span>
        </a>
      </li>
    @endif

    <li class="{{ $tab == 'account' ? 'active' : '' }}">
      <a href="{{ route('account', 'account') }}">
        <i class="fas fa-user-cog"></i>
        <span>@lang('theme.nav.my_account')</span>
      </a>
    </li>
  </ul>
</aside>
