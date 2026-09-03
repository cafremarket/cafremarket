<footer class="sf-footer">
  <div class="sf-footer-newsletter">
    <div class="container">
      <div class="sf-footer-newsletter__inner">
        <div class="sf-footer-newsletter__text">
          <span class="sf-footer-newsletter__icon"><i class="fal fa-envelope-open-text"></i></span>
          <div>
            <h3>{{ trans('theme.newsletter_subscribe') }}</h3>
            <p>{{ trans('theme.newsletter_description') }}</p>
          </div>
        </div>

        <div class="sf-footer-newsletter__form">
          {!! Form::open(['route' => 'newsletter.subscribe', 'class' => 'subscribe-form', 'id' => 'footer-newsletter-form', 'data-toggle' => 'validator']) !!}
          <div class="sf-footer-newsletter__input-group">
            {!! Form::email('email', null, ['placeholder' => trans('theme.placeholder.email'), 'required', 'aria-label' => trans('theme.placeholder.email')]) !!}
            <button type="submit">{{ trans('theme.button.subscribe') }}</button>
          </div>
          {!! Form::close() !!}
        </div>
      </div>
    </div>
  </div>

  <div class="sf-footer-main">
    <div class="container">
      <div class="sf-footer-grid">
        <div class="sf-footer-brand">
          <a href="{{ url('/') }}" class="sf-footer-brand__logo">
            @include('theme::partials._site_logo', ['wrapLink' => false, 'class' => 'sf-footer-brand__logo-img'])
          </a>

          @if (config('system_settings.slogan'))
            <p class="sf-footer-brand__slogan">{!! config('system_settings.slogan') !!}</p>
          @endif

          <ul class="sf-footer-contact">
            @if (get_platform_address_string())
              <li>
                <i class="fal fa-map-marker-alt"></i>
                <span>{!! get_platform_address_string() !!}</span>
              </li>
            @endif

            @if (config('system_settings.support_phone'))
              <li>
                <i class="fal fa-phone"></i>
                <a href="tel:{{ config('system_settings.support_phone') }}">{{ config('system_settings.support_phone') }}</a>
              </li>
            @endif

            @if (config('system_settings.support_email'))
              <li>
                <i class="fal fa-envelope"></i>
                <a href="mailto:{{ config('system_settings.support_email') }}">{{ config('system_settings.support_email') }}</a>
              </li>
            @endif
          </ul>
        </div>

        <div class="sf-footer-col">
          <h4>{{ trans('theme.nav.let_us_help') }}</h4>
          <ul>
            <li><a href="{{ route('account', 'account') }}" rel="nofollow">{{ trans('theme.nav.your_account') }}</a></li>
            <li><a href="{{ route('account', 'orders') }}" rel="nofollow">{{ trans('theme.nav.your_orders') }}</a></li>
            <li><a href="{{ route('blog') }}" target="_blank" rel="noopener">{{ trans('theme.nav.blog') }}</a></li>
            @foreach (($pages ?? collect())->where('position', 'footer_1st_column') as $page)
              <li><a href="{{ get_page_url($page->slug) }}" rel="nofollow noopener" target="_blank">{{ $page->title }}</a></li>
            @endforeach
          </ul>
        </div>

        <div class="sf-footer-col">
          <h4>{{ trans('theme.nav.make_money') }}</h4>
          <ul>
            <li>
              <a href="{{ route('selling.login') }}" class="sf-footer-seller-link">
                <i class="fal fa-store"></i> {{ trans('theme.nav.seller_login') }}
              </a>
            </li>
            <li><a href="{{ url('/selling') }}">{{ trans('theme.nav.sell_on', ['platform' => get_platform_title()]) }}</a></li>
            <li><a href="{{ url('/selling#pricing') }}">{{ trans('theme.nav.become_merchant') }}</a></li>
            <li><a href="{{ url('/selling#howItWorks') }}">{{ trans('theme.nav.how_it_works') }}</a></li>
            <li><a href="{{ url('/selling#faqs') }}">{{ trans('theme.nav.faq') }}</a></li>
            @if (is_incevio_package_loaded('affiliate'))
              <li><a href="{{ route('affiliate.register.form') }}">{{ trans('packages.affiliate.become_an_affiliate') }}</a></li>
            @endif
            @foreach (($pages ?? collect())->where('position', 'footer_2nd_column') as $page)
              <li><a href="{{ get_page_url($page->slug) }}" rel="nofollow" target="_blank">{{ $page->title }}</a></li>
            @endforeach
          </ul>
        </div>

        <div class="sf-footer-col">
          <h4>{{ trans('theme.nav.customer_service') }}</h4>
          <ul>
            <li><a href="{{ route('account', 'disputes') }}">{{ trans('theme.nav.refunds_disputes') }}</a></li>
            <li><a href="{{ route('account', 'orders') }}">{{ trans('theme.nav.contact_seller') }}</a></li>
            <li><a href="{{ get_page_url(\App\Models\Page::PAGE_CONTACT_US) }}">{{ trans('theme.nav.contact_us') }}</a></li>
            @foreach (($pages ?? collect())->where('position', 'footer_3rd_column') as $page)
              <li><a href="{{ get_page_url($page->slug) }}" rel="nofollow" target="_blank">{{ $page->title }}</a></li>
            @endforeach
          </ul>
        </div>

        <div class="sf-footer-col sf-footer-col--social">
          <h4>{{ trans('theme.stay_connected') }}</h4>

          @if ($social_media_links = get_social_media_links())
            <ul class="sf-footer-social">
              @foreach ($social_media_links as $social_media => $link)
                <li>
                  <a href="{{ $link }}" target="_blank" rel="noopener" aria-label="{{ $social_media }}" title="{{ $social_media }}">
                    <i class="fa fa-{{ $social_media }}"></i>
                  </a>
                </li>
              @endforeach
            </ul>
          @endif

          @if ($trust_badge = get_trust_badge_url())
            <div class="sf-footer-trust">
              <img src="{{ $trust_badge }}" alt="{{ trans('theme.trust_badge') }}">
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</footer>

@include('theme::nav.copyright')
