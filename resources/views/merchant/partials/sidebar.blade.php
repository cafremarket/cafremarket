<aside class="mp-sidebar" id="mp-sidebar">
  <div class="mp-sidebar__brand">
    @if (system_has_custom_logo())
      <img src="{{ get_logo_url('system', 'logo') }}" alt="{{ get_platform_title() }}">
    @endif
    <span>{{ optional(Auth::user()->shop)->name ?? trans('app.merchant') }}</span>
  </div>

  <nav class="mp-sidebar__nav" id="mp-sidebar-nav">
    {{-- Overview --}}
    <div class="mp-nav-group {{ mp_is_any(['merchant/dashboard*', 'merchant/verify*']) ? 'is-open' : '' }}">
      <button type="button" class="mp-nav-group__toggle" aria-expanded="{{ mp_is_any(['merchant/dashboard*', 'merchant/verify*']) ? 'true' : 'false' }}">
        <i class="fa fa-th-large"></i>
        <span>{{ trans('nav.dashboard') ?? 'Overview' }}</span>
        <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
      </button>
      <div class="mp-nav-group__items">
        <div class="mp-nav-group__items-inner">
          <a href="{{ route('merchant.dashboard') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/dashboard*') ? 'is-active' : '' }}">
            <i class="fa fa-dashboard"></i>
            <span>{{ trans('nav.dashboard') }}</span>
          </a>

          @if (optional(Auth::user()->shop)->exists && ! Auth::user()->shop->isVerified())
            <a href="{{ route('merchant.verify') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/verify*') ? 'is-active' : '' }}">
              <i class="fa fa-shield"></i>
              <span>{{ trans('app.get_verified') }}</span>
              @if (optional(Auth::user()->shop->config)->wasVerificationRejected())
                <span class="mp-sidebar__badge">{{ trans('app.verification_rejected') }}</span>
              @endif
            </a>
          @endif

          @if (optional(Auth::user()->shop)->slug)
            <a href="{{ get_shop_url() }}" class="mp-sidebar__link mp-sidebar__link--sub" target="_blank" rel="noopener">
              <i class="fa fa-external-link"></i>
              <span>{{ trans('app.store_front') ?? 'My store page' }}</span>
            </a>
          @endif
        </div>
      </div>
    </div>

    @include('merchant.partials.sidebar_menu')
  </nav>
</aside>
