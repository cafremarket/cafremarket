<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', trans('messages.seller_onboarding_title')) — {{ get_platform_title() }}</title>
  <link rel="icon" href="{{ get_icon_url('system', 'thumbnail') }}" type="image/x-icon" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
  <link href="{{ asset('css/merchant-panel.css') }}?v={{ filemtime(public_path('css/merchant-panel.css')) }}" rel="stylesheet">
  @yield('head')
</head>
<body class="mp-body mp-onboarding">
  <header class="mp-onboarding__header">
    <a href="{{ url('/') }}" class="mp-onboarding__brand">
      @if (system_has_custom_logo())
        <img src="{{ get_logo_url('system', 'logo') }}" alt="{{ get_platform_title() }}">
      @else
        <span>{{ get_platform_title() }}</span>
      @endif
    </a>
    <div class="mp-onboarding__user">
      @include('merchant.partials.language_switcher')
      {{ Auth::user()->getName() }}
      &middot;
      <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('mp-logout').submit();">{{ trans('app.log_out') ?? trans('app.logout') }}</a>
      <form id="mp-logout" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
    </div>
  </header>

  <main class="mp-onboarding__main">
    @if (Session::has('success'))
      <div class="mp-alert mp-alert--success"><i class="fa fa-check-circle"></i> {{ Session::get('success') }}</div>
    @endif
    @if (Session::has('info'))
      <div class="mp-alert mp-alert--info"><i class="fa fa-info-circle"></i> {{ Session::get('info') }}</div>
    @endif
    @if (Session::has('error'))
      <div class="mp-alert mp-alert--danger"><i class="fa fa-times-circle"></i> {{ Session::get('error') }}</div>
    @endif
    @if ($errors->any())
      <div class="mp-alert mp-alert--danger">
        <i class="fa fa-exclamation-circle"></i>
        <ul style="margin:0;padding-left:16px">
          @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
      </div>
    @endif

    @yield('content')
  </main>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
  @yield('scripts')
  @include('scripts.password_toggle')
  @include('scripts.google_place')
</body>
</html>
