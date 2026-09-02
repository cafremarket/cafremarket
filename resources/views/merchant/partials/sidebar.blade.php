<aside class="mp-sidebar" id="mp-sidebar">
  <div class="mp-sidebar__brand">
    <img src="{{ get_logo_url('system', 'tiny') }}" alt="{{ get_platform_title() }}">
    <span>{{ optional(Auth::user()->shop)->name ?? trans('app.merchant') }}</span>
  </div>

  <nav class="mp-sidebar__nav" id="mp-sidebar-nav">
    {{-- Overview --}}
    <div class="mp-sidebar__section">{{ trans('nav.dashboard') ?? 'Overview' }}</div>
    <a href="{{ route('merchant.dashboard') }}" class="mp-sidebar__link {{ mp_is('merchant/dashboard*') ? 'is-active' : '' }}">
      <i class="fa fa-dashboard"></i> {{ trans('nav.dashboard') }}
    </a>

    @if (optional(Auth::user()->shop)->exists && ! Auth::user()->shop->isVerified())
      <a href="{{ route('merchant.verify') }}" class="mp-sidebar__link {{ mp_is('merchant/verify*') ? 'is-active' : '' }}">
        <i class="fa fa-shield"></i> {{ trans('app.get_verified') }}
        @if (optional(Auth::user()->shop->config)->wasVerificationRejected())
          <span class="mp-sidebar__badge">{{ trans('app.verification_rejected') }}</span>
        @endif
      </a>
    @endif

    @if (optional(Auth::user()->shop)->slug)
      <a href="{{ get_shop_url() }}" class="mp-sidebar__link" target="_blank" rel="noopener">
        <i class="fa fa-external-link"></i> {{ trans('app.store_front') ?? 'My store page' }}
        <span class="mp-sidebar__slug">{{ Auth::user()->shop->slug }}</span>
      </a>
    @endif

    @include('merchant.partials.sidebar_menu')
  </nav>
</aside>
