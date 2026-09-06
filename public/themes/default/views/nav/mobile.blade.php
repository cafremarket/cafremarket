<div class="main-menu mobile-mega-menu sf-mobile-drawer" id="sf-mobile-drawer" aria-hidden="true">
  <div class="sf-mobile-drawer__header">
    <a href="{{ url('/') }}" class="sf-mobile-drawer__brand" aria-label="{{ get_platform_title() }}">
      @include('theme::partials._site_logo', ['wrapLink' => false, 'class' => 'sf-mobile-drawer__logo', 'height' => 36])
    </a>
    <button type="button" class="sf-mobile-drawer__close main-menu-toggle" aria-label="{{ trans('theme.close') ?? 'Close menu' }}">
      <i class="fal fa-times" aria-hidden="true"></i>
    </button>
  </div>

  <nav class="sf-mobile-drawer__nav" aria-label="{{ trans('theme.menu') ?? 'Menu' }}">
    <a href="{{ url('/') }}" class="sf-mobile-drawer__link">
      <span class="sf-mobile-drawer__icon"><i class="fal fa-home" aria-hidden="true"></i></span>
      <span>{{ trans('theme.home') ?? 'Home' }}</span>
    </a>

    <a href="{{ route('shops') }}" class="sf-mobile-drawer__link">
      <span class="sf-mobile-drawer__icon"><i class="fal fa-store" aria-hidden="true"></i></span>
      <span>{{ trans('theme.stores') }}</span>
    </a>

    @auth('customer')
      <a href="{{ route('account', 'dashboard') }}" class="sf-mobile-drawer__link">
        <span class="sf-mobile-drawer__icon"><i class="fal fa-tachometer-alt" aria-hidden="true"></i></span>
        <span>{{ trans('theme.nav.dashboard') }}</span>
      </a>

      <a href="{{ route('account', 'account') }}" class="sf-mobile-drawer__link">
        <span class="sf-mobile-drawer__icon"><i class="fal fa-user-cog" aria-hidden="true"></i></span>
        <span>{{ trans('theme.nav.my_account') }}</span>
      </a>

      <a href="{{ route('account', 'orders') }}" class="sf-mobile-drawer__link">
        <span class="sf-mobile-drawer__icon"><i class="fal fa-shopping-bag" aria-hidden="true"></i></span>
        <span>{{ trans('theme.nav.my_orders') }}</span>
      </a>
    @else
      <a href="javascript:void(0);" data-toggle="modal" data-target="#loginModal" class="sf-mobile-drawer__link sf-mobile-drawer__link--login">
        <span class="sf-mobile-drawer__icon"><i class="fal fa-sign-in" aria-hidden="true"></i></span>
        <span>{{ trans('theme.login') }}</span>
      </a>
    @endauth

    @if (false)
      <a href="#" class="sf-mobile-drawer__link">
        <span class="sf-mobile-drawer__icon"><i class="fal fa-crown" aria-hidden="true"></i></span>
        <span>{{ trans('theme.brands') }}</span>
      </a>
    @endif

    <a href="{{ url('/selling') }}" class="sf-mobile-drawer__link sf-mobile-drawer__link--accent">
      <span class="sf-mobile-drawer__icon"><i class="fal fa-seedling" aria-hidden="true"></i></span>
      <span>{{ trans('theme.sell') }}</span>
    </a>
  </nav>

  <div class="sf-mobile-drawer__footer">
    <div class="sf-mobile-drawer__lang">
      <label for="mobile-lang" class="sf-mobile-drawer__lang-label">{{ trans('theme.language') ?? 'Language' }}</label>
      <select name="lang" id="mobile-lang">
        @foreach (config('active_locales') as $lang)
          <option dd-link="{{ route('locale.change', $lang->code) }}" value="{{ $lang->code }}" data-imagesrc="{{ get_flag_img_by_code(array_slice(explode('_', $lang->php_locale_code), -1)[0], true) }}" {{ $lang->code == \App::getLocale() ? 'selected' : '' }}>
            {{ $lang->language ?? $lang->code }}
          </option>
        @endforeach
      </select>
    </div>

    @if (is_incevio_package_loaded('dynamic-currency'))
      <div class="sf-mobile-drawer__lang">
        <label for="currencyChange" class="sf-mobile-drawer__lang-label">{{ trans('theme.currency') ?? 'Currency' }}</label>
        <select name="currency" id="currencyChange">
          @foreach (get_active_currencies() as $item)
            @php
              if (get_dynamic_currency_attr('iso_code') == $item->iso_code) {
                  $selected = 'selected';
              } elseif (!session()->has('currency') && $item->iso_code == get_system_currency()) {
                  $selected = 'selected';
              } else {
                  $selected = '';
              }
            @endphp
            <option value="{{ $item->iso_code }}" {{ $selected ?? '' }}>
              {{ $item->iso_code ?? '' }} ({{ $item->symbol ?? '' }})
            </option>
          @endforeach
        </select>
      </div>
    @endif

    @auth('customer')
      <a href="{{ route('customer.logout') }}" class="sf-mobile-drawer__logout">
        <i class="fal fa-sign-out" aria-hidden="true"></i>
        {{ trans('theme.logout') }}
      </a>
    @endauth
  </div>
</div>
