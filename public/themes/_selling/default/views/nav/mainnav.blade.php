<header class="sf-sell-header" id="sfSellHeader">
  <div class="container sf-sell-header__inner">
    <a href="{{ url('/') }}" class="sf-sell-header__brand">
      @if (system_has_custom_logo())
        <img src="{{ get_logo_url('system', 'logo') }}" class="sf-sell-header__logo" alt="{{ get_platform_brand_label() }}">
      @else
        <span class="sf-sell-header__brand-text">{{ get_platform_brand_label() }}</span>
      @endif
    </a>

    <ul class="sf-sell-header__nav">
      <li><a href="#benefits" class="sf-sell-scroll-link">{{ trans('theme.benefits') }}</a></li>
      <li><a href="#howItWorks" class="sf-sell-scroll-link">{{ trans('theme.how_it_works') }}</a></li>
      @if (is_subscription_enabled())
        <li><a href="#pricing" class="sf-sell-scroll-link">{{ trans('theme.pricing') }}</a></li>
      @endif
      <li><a href="#faqs" class="sf-sell-scroll-link">{{ trans('theme.faq') }}</a></li>
      <li><a href="#contact" class="sf-sell-scroll-link">{{ trans('theme.nav.contact_us') }}</a></li>
    </ul>

    <div class="sf-sell-header__actions">
      <a href="{{ route('selling.login') }}" class="sf-sell-btn sf-sell-btn--ghost">{{ trans('app.form.login') }}</a>
      <a href="{{ route('selling.register') }}" class="sf-sell-btn sf-sell-btn--primary">{{ trans('theme.button.selling') }}</a>
      <button type="button" class="sf-sell-header__toggle" id="sfSellNavToggle" aria-label="{{ trans('theme.nav.toggle_navigation') }}">
        <i class="fa fa-bars"></i>
      </button>
    </div>
  </div>
</header>

<div class="sf-sell-mobile-nav" id="sfSellMobileNav">
  <div class="sf-sell-mobile-nav__panel">
    <a href="#benefits" class="sf-sell-scroll-link">{{ trans('theme.benefits') }}</a>
    <a href="#howItWorks" class="sf-sell-scroll-link">{{ trans('theme.how_it_works') }}</a>
    @if (is_subscription_enabled())
      <a href="#pricing" class="sf-sell-scroll-link">{{ trans('theme.pricing') }}</a>
    @endif
    <a href="#faqs" class="sf-sell-scroll-link">{{ trans('theme.faq') }}</a>
    <a href="#contact" class="sf-sell-scroll-link">{{ trans('theme.nav.contact_us') }}</a>
    <a href="{{ route('selling.login') }}">{{ trans('app.form.login') }}</a>
    <a href="{{ route('selling.register') }}">{{ trans('theme.button.selling') }}</a>
  </div>
</div>
