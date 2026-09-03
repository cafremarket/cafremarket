<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', trans('nav.dashboard')) — {{ optional(Auth::user()->shop)->name ?? get_platform_title() }}</title>
  <link rel="icon" href="{{ get_icon_url('system', 'thumbnail') }}" type="image/x-icon" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
  <link href="{{ asset('css/merchant-panel.css') }}?v={{ @filemtime(public_path('css/merchant-panel.css')) ?: time() }}" rel="stylesheet">
  @yield('head')
</head>
<body class="mp-body">
  <script>window.__merchantPanel = true;</script>
  <div class="mp-app">
    @include('merchant.partials.sidebar')

    <div class="mp-main">
      <header class="mp-topbar">
        <h1 class="mp-topbar__title">@yield('page_title', trans('nav.dashboard'))</h1>
        <div class="mp-topbar__actions">
          @include('merchant.partials.language_switcher')
          <a href="{{ get_shop_url() }}" target="_blank" rel="noopener"><i class="fa fa-external-link"></i> {{ trans('app.store_front') ?? 'My store' }}</a>
          <span>{{ Auth::user()->getName() }}</span>
          <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('mp-logout').submit();">{{ trans('app.log_out') ?? trans('app.logout') }}</a>
          <form id="mp-logout" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
        </div>
      </header>

      <div class="mp-content">
        @if (Session::has('success'))
          <div class="mp-alert mp-alert--success"><i class="fa fa-check-circle"></i> {{ Session::get('success') }}</div>
        @endif
        @if (Session::has('error'))
          <div class="mp-alert mp-alert--danger"><i class="fa fa-times-circle"></i> {{ Session::get('error') }}</div>
        @endif
        @yield('content')
      </div>
    </div>
  </div>

  <div id="myDynamicModal" class="modal fade" aria-hidden="true" data-backdrop="static" data-keyboard="false"></div>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    (function () {
      var nav = document.getElementById('mp-sidebar-nav');
      if (!nav) return;

      function setOpen(group, open) {
        group.classList.toggle('is-open', open);
        var btn = group.querySelector('.mp-nav-group__toggle');
        if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      }

      nav.querySelectorAll('.mp-nav-group__toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var group = btn.closest('.mp-nav-group');
          if (!group) return;

          var willOpen = !group.classList.contains('is-open');

          // Accordion: close other groups when opening one
          if (willOpen) {
            nav.querySelectorAll('.mp-nav-group.is-open').forEach(function (other) {
              if (other !== group) setOpen(other, false);
            });
          }

          setOpen(group, willOpen);
        });
      });
    })();
  </script>
  @include('scripts.password_toggle')
  @include('scripts.google_place')
  @yield('scripts')
</body>
</html>
