<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, shrink-to-fit=no">
  <meta name="author" content="{{ config('system_settings.name') ?? config('app.name') }}">
  <title>
    @hasSection('page_title')
      @yield('page_title') —
    @elseif(isset($page_title))
      {!! strip_tags($page_title) !!} —
    @endif
    {{ trans('packages.affiliate.affiliate') }} — {{ get_platform_title() }}
  </title>

  <link rel="manifest" href="{{ asset('site.webmanifest') }}">
  <link rel="icon" href="{{ get_icon_url('system', 'thumbnail') }}" type="image/x-icon" />
  <link rel="apple-touch-icon" href="{{ get_icon_url('system', 'thumbnail') }}">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
  <link href="{{ asset('css/admin-modern.css') }}?v={{ @filemtime(public_path('css/admin-modern.css')) ?: time() }}" rel="stylesheet">
  <link href="{{ asset('css/merchant-panel.css') }}?v={{ @filemtime(public_path('css/merchant-panel.css')) ?: time() }}" rel="stylesheet">

  @yield('page-style')

  @if (is_incevio_package_loaded('otp-login'))
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
  @endif
  <style>.iti { display: block; }</style>
</head>

<body class="mp-body mp-body--panel">
  <script>window.__merchantPanel = true;</script>
  <div class="mp-app">
    @include('affiliate::backend._sidebar')
    <div class="mp-sidebar-overlay" id="mp-sidebar-overlay" hidden aria-hidden="true"></div>

    <div class="mp-main">
      <header class="mp-topbar">
        <button type="button" class="mp-sidebar-toggle" id="mp-sidebar-toggle" aria-label="{{ trans('app.toggle_navigation') }}" aria-expanded="false" aria-controls="mp-sidebar">
          <i class="fa fa-bars"></i>
        </button>
        <h1 class="mp-topbar__title">
          @hasSection('page_title')
            @yield('page_title')
          @elseif(isset($page_title))
            {!! strip_tags($page_title) !!}
          @else
            {{ trans('packages.affiliate.affiliate') }}
          @endif
        </h1>
        <div class="mp-topbar__actions">
          <a href="{{ url('/') }}" target="_blank" rel="noopener"><i class="fa fa-external-link"></i> {{ trans('app.store_front') }}</a>
          <a href="{{ route('affiliate.profile') }}">{{ auth()->guard('affiliate')->user()->getName() }}</a>
          @if (Request::session()->has('impersonated'))
            <a href="{{ route('admin.secretLogout') }}">{{ trans('app.log_out') }}</a>
          @else
            <a href="{{ route('affiliate.logout') }}">{{ trans('app.log_out') }}</a>
          @endif
        </div>
      </header>

      <div class="mp-content mp-content--admin">
        @if (View::hasSection('page_title') || View::hasSection('buttons') || isset($page_title))
          @include('affiliate::backend._page_header')
        @endif

        @include('admin.partials.ui.alerts')

        @if (count($errors) > 0)
          <div class="alert alert-danger">
            <strong>{{ trans('app.error') }}!</strong> {{ trans('messages.input_error') }}<br><br>
            <ul class="list-group">
              @foreach ($errors->all() as $error)
                <li class="list-group-item list-group-item-danger">{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

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

  @include('affiliate::scripts.footer_js')
  <script src="{{ asset('js/admin-modern.js') }}"></script>
  @include('scripts.password_toggle')
  @stack('script')
</body>
</html>
