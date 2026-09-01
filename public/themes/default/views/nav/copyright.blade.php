<div class="sf-footer-bottom">
  <div class="container">
    <div class="sf-footer-bottom__inner">
      <p class="sf-footer-bottom__copy">
        &copy; {{ date('Y') }} <a href="{{ url('/') }}">{{ get_platform_title() }}</a>
      </p>

      <ul class="sf-footer-bottom__links">
        @foreach (($pages ?? collect())->where('position', 'copyright_area') as $page)
          <li><a href="{{ get_page_url($page->slug) }}" target="_blank" rel="noopener">{{ $page->title }}</a></li>
        @endforeach

        <li>
          <a href="{{ route('selling.login') }}">{{ trans('theme.nav.seller_login') }}</a>
        </li>

        <li><a href="{{ url('merchant/dashboard') }}">{{ trans('theme.nav.merchant_dashboard') }}</a></li>

        @if (is_incevio_package_loaded('affiliate'))
          @if (auth()->guard('affiliate')->check())
            <li><a href="{{ route('affiliate.dashboard') }}">{{ trans('packages.affiliate.affiliate_dashboard') }}</a></li>
          @else
            <li><a href="{{ route('affiliate.login') }}">{{ trans('packages.affiliate.login') }}</a></li>
          @endif
        @endif
      </ul>
    </div>
  </div>
</div>
