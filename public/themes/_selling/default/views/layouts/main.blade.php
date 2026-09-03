<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, shrink-to-fit=no">
  <meta name="description" content="{{ trans('theme.selling_page.meta_description', ['platform' => get_platform_title()]) }}">
  <title>{{ trans('theme.selling_page.page_title', ['platform' => get_platform_title()]) }}</title>
  <link rel="icon" href="{{ get_icon_url('system', 'thumbnail') }}" type="image/x-icon" />
  <link rel="manifest" href="{{ asset('site.webmanifest') }}">
  <link rel="apple-touch-icon" href="{{ get_icon_url('system', 'thumbnail') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <link href="{{ selling_theme_asset_url('css/vendor.css') }}" rel="stylesheet">
  <link href="{{ selling_theme_asset_url('css/selling-modern.css') }}?v={{ @filemtime(selling_theme_assets_path().'/css/selling-modern.css') ?: time() }}" rel="stylesheet">
</head>

<body class="sf-selling-page">
  @include('nav.mainnav')

  <section class="sf-sell-hero">
    <div class="container">
      <div class="sf-sell-hero__grid">
        <div class="sf-sell-hero__content">
          <span class="sf-sell-hero__badge">
            <i class="fa fa-store"></i>
            {{ trans('theme.selling_page.hero_badge', ['platform' => get_platform_title()]) }}
          </span>
          <h1 class="sf-sell-hero__title">
            {!! trans('theme.selling_page.hero_title') !!}
          </h1>
          <p class="sf-sell-hero__lead">{{ trans('theme.selling_page.hero_lead') }}</p>
          <div class="sf-sell-hero__actions">
            <a href="{{ route('selling.register') }}" class="sf-sell-btn sf-sell-btn--primary sf-sell-btn--lg">
              <i class="fa fa-rocket"></i> {{ trans('theme.button.selling') }}
            </a>
            <a href="#howItWorks" class="sf-sell-btn sf-sell-btn--ghost sf-sell-btn--lg sf-sell-scroll-link">
              {{ trans('theme.selling_page.hero_secondary_cta') }}
            </a>
          </div>
          <p class="sf-sell-hero__note" id="sfSellHeroNote">{{ trans('theme.selling_page.hero_free_note') }}</p>
        </div>

        <div class="sf-sell-hero__visual">
          <div class="sf-sell-hero__card">
            <div class="sf-sell-hero__stats">
              <div class="sf-sell-hero__stat">
                <span class="sf-sell-hero__stat-value">{{ trans('theme.selling_page.stat_1_value') }}</span>
                <span class="sf-sell-hero__stat-label">{{ trans('theme.selling_page.stat_1_label') }}</span>
              </div>
              <div class="sf-sell-hero__stat">
                <span class="sf-sell-hero__stat-value">{{ trans('theme.selling_page.stat_2_value') }}</span>
                <span class="sf-sell-hero__stat-label">{{ trans('theme.selling_page.stat_2_label') }}</span>
              </div>
              <div class="sf-sell-hero__stat">
                <span class="sf-sell-hero__stat-value">{{ trans('theme.selling_page.stat_3_value') }}</span>
                <span class="sf-sell-hero__stat-label">{{ trans('theme.selling_page.stat_3_label') }}</span>
              </div>
              <div class="sf-sell-hero__stat">
                <span class="sf-sell-hero__stat-value">{{ trans('theme.selling_page.stat_4_value') }}</span>
                <span class="sf-sell-hero__stat-label">{{ trans('theme.selling_page.stat_4_label') }}</span>
              </div>
            </div>
            <ul class="sf-sell-hero__feature-list">
              <li><i class="fa fa-check-circle"></i> {{ trans('theme.selling_page.hero_point_1') }}</li>
              <li><i class="fa fa-check-circle"></i> {{ trans('theme.selling_page.hero_point_2') }}</li>
              <li><i class="fa fa-check-circle"></i> {{ trans('theme.selling_page.hero_point_3') }}</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <nav class="sf-sell-section-nav" id="sfSellSectionNav">
    <div class="container sf-sell-section-nav__inner">
      <a href="#benefits" class="sf-sell-section-nav__link sf-sell-scroll-link">{{ trans('theme.benefits') }}</a>
      <a href="#howItWorks" class="sf-sell-section-nav__link sf-sell-scroll-link">{{ trans('theme.how_it_works') }}</a>
      @if (is_subscription_enabled())
        <a href="#pricing" class="sf-sell-section-nav__link sf-sell-scroll-link" data-api-nav="pricing">{{ trans('theme.pricing') }}</a>
      @endif
      <a href="#faqs" class="sf-sell-section-nav__link sf-sell-scroll-link">{{ trans('theme.faq') }}</a>
      <a href="#contact" class="sf-sell-section-nav__link sf-sell-scroll-link">{{ trans('theme.nav.contact_us') }}</a>
    </div>
  </nav>

  <main>
    @yield('content')
  </main>

  <section class="sf-sell-cta">
    <div class="container sf-sell-cta__inner">
      <div>
        <h2 class="sf-sell-cta__title">{{ trans('theme.selling_page.cta_title') }}</h2>
        <p class="sf-sell-cta__text">{{ trans('theme.selling_page.cta_text') }}</p>
      </div>
      <a href="{{ route('selling.register') }}" class="sf-sell-btn sf-sell-btn--primary sf-sell-btn--lg">
        {{ trans('theme.selling_page.cta_button') }}
      </a>
    </div>
  </section>

  <section id="contact" class="sf-sell-contact">
    @include('contact')
  </section>

  <footer class="sf-sell-footer">
    <div class="container">
      <ul class="sf-sell-footer__links">
        <li><a href="{{ route('page.open', \App\Models\Page::PAGE_ABOUT_US) }}" target="_blank">{{ trans('theme.nav.about_us') }}</a></li>
        <li><a href="{{ route('page.open', \App\Models\Page::PAGE_PRIVACY_POLICY) }}" target="_blank">{{ trans('theme.nav.privacy_policy') }}</a></li>
        <li><a href="{{ route('page.open', \App\Models\Page::PAGE_TNC_FOR_MERCHANT) }}" target="_blank">{{ trans('theme.nav.term_and_conditions') }}</a></li>
        <li><a href="{{ route('page.open', \App\Models\Page::PAGE_RETURN_AND_REFUND) }}" target="_blank">{{ trans('theme.nav.return_and_refund_policy') }}</a></li>
        <li><a href="{{ url('/') }}">{{ trans('theme.selling_page.back_to_store') }}</a></li>
      </ul>

      <div class="sf-sell-footer__bottom">
        <span>© {{ date('Y') }} {{ get_platform_title() }}. {{ trans('theme.selling_page.footer_rights') }}</span>
        @if ($social_media_links = get_social_media_links())
          <ul class="sf-sell-footer__social">
            @foreach ($social_media_links as $social_media => $link)
              <li><a href="{{ $link }}" target="_blank" rel="noopener"><i class="fa fa-{{ $social_media }}"></i></a></li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  </footer>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
  <script src="{{ selling_theme_asset_url('js/jqBootstrapValidation.min.js') }}"></script>
  <script src="{{ selling_theme_asset_url('js/app.js') }}?v={{ @filemtime(selling_theme_assets_path().'/js/app.js') ?: time() }}"></script>
  @yield('scripts')
</body>

</html>
