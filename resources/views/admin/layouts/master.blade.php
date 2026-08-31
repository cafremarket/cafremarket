<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
@php
  $useMerchantPanelLayout = request()->is('merchant/*')
    && Auth::check()
    && Auth::user()->isFromMerchant();
@endphp
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, shrink-to-fit=no">
  <title>
    @if ($useMerchantPanelLayout ?? false)
      @hasSection('page_title')
        @yield('page_title') —
      @elseif(isset($page_title))
        {!! strip_tags($page_title) !!} —
      @endif
      {{ optional(Auth::user()->shop)->name ?? get_platform_title() }}
    @else
      {!! $title ?? get_site_title() !!}
    @endif
  </title>

  <link rel="manifest" href="{{ asset('site.webmanifest') }}">
  <link rel="icon" href="{{ get_storage_file_url('icon.png', 'full') }}" type="image/x-icon" />
  <link rel="apple-touch-icon" href="{{ get_storage_file_url('icon.png', 'full') }}">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
  <link href="{{ asset('css/admin-modern.css') }}" rel="stylesheet">
  @if ($useMerchantPanelLayout ?? false)
    <link href="{{ asset('css/merchant-panel.css') }}" rel="stylesheet">
  @endif

  @yield('page-style')

  @if (is_incevio_package_loaded('otp-login'))
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
  @endif
  <style>.iti { display: block; }</style>
</head>

@if ($useMerchantPanelLayout)
<body class="mp-body mp-body--panel">
  <script>window.__merchantPanel = true;</script>
  <div class="mp-app">
    @include('merchant.partials.sidebar')

    <div class="mp-main">
      <header class="mp-topbar">
        <h1 class="mp-topbar__title">
          @hasSection('page_title')
            @yield('page_title')
          @elseif(isset($page_title))
            {!! strip_tags($page_title) !!}
          @else
            {{ trans('nav.dashboard') }}
          @endif
        </h1>
        <div class="mp-topbar__actions">
          @include('merchant.partials.language_switcher')
          <a href="{{ get_shop_url() }}" target="_blank" rel="noopener"><i class="fa fa-external-link"></i> {{ trans('app.store_front') ?? 'My store' }}</a>
          <span>{{ Auth::user()->getName() }}</span>
          <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('mp-logout').submit();">{{ trans('app.log_out') ?? trans('app.logout') }}</a>
          <form id="mp-logout" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
        </div>
      </header>

      <div class="mp-content mp-content--admin">
        @if (View::hasSection('page_title') || View::hasSection('buttons') || isset($page_title))
          @include('merchant.partials.page_header')
        @endif

        @include('admin.partials.ui.alerts')
        @yield('content')
      </div>
    </div>
  </div>

  <div id="myDynamicModal" class="modal fade admin-modal" aria-hidden="true" data-backdrop="static" data-keyboard="false"></div>

  <div class="loader admin-loader">
    <div class="admin-loader__spinner"></div>
  </div>

  <script src="{{ asset('js/app.js') }}"></script>
  @include('admin.notification')
  @yield('page-script')

  @if (is_incevio_package_loaded('otp-login'))
    @include('otp-login::scripts')
  @endif

  @include('admin.footer_js')
  <script src="{{ asset('js/admin-modern.js') }}"></script>
  @include('scripts.password_toggle')
  @include('scripts.google_place')
  @stack('script')
  @yield('scripts')
</body>
@else
<body class="hold-transition skin-black sidebar-mini admin-modern">
  <div class="wrapper">
    @include('admin.header')
    @include('admin.sidebar')

    <div class="content-wrapper admin-content">
      @if (View::hasSection('page_title') || View::hasSection('buttons') || isset($page_title))
        @include('admin.partials.page_header')
      @endif

      <section class="content admin-content__body">
        @include('admin.partials.ui.alerts')
        @yield('content')
      </section>
    </div>

    @include('admin.footer')
    <div id="myDynamicModal" class="modal fade admin-modal" aria-hidden="true" data-backdrop="static" data-keyboard="false"></div>
  </div>

  <div class="loader admin-loader">
    <div class="admin-loader__spinner"></div>
  </div>

  <script src="{{ asset('js/app.js') }}"></script>
  @include('admin.notification')
  @yield('page-script')

  @if (is_incevio_package_loaded('otp-login'))
    @include('otp-login::scripts')
  @endif

  @include('admin.footer_js')
  <script src="{{ asset('js/admin-modern.js') }}"></script>
  @include('scripts.password_toggle')
  @include('scripts.google_place')
  @stack('script')
</body>
@endif
</html>
