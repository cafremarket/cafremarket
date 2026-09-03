<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, shrink-to-fit=no">
  <title>@yield('page_title', trans('app.form.login')) — {{ get_platform_title() }}</title>
  <link rel="icon" href="{{ get_icon_url('system', 'thumbnail') }}" type="image/x-icon" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="{{ selling_theme_asset_url('css/vendor.css') }}" rel="stylesheet">
  <link href="{{ selling_theme_asset_url('css/selling-modern.css') }}?v={{ @filemtime(selling_theme_assets_path().'/css/selling-modern.css') ?: time() }}" rel="stylesheet">
</head>

<body class="sf-selling-page sf-selling-auth-page">
  @include('nav.mainnav')

  <section class="sf-sell-auth">
    <div class="container">
      <div class="sf-sell-auth__grid">
        <div class="sf-sell-auth__intro">
          <span class="sf-sell-hero__badge">
            <i class="fa fa-store"></i>
            {{ trans('theme.selling_page.hero_badge', ['platform' => get_platform_title()]) }}
          </span>
          <h1 class="sf-sell-auth__title">@yield('auth_heading')</h1>
          <p class="sf-sell-auth__lead">@yield('auth_subheading')</p>
          <ul class="sf-sell-auth__points">
            <li><i class="fa fa-check-circle"></i> {{ trans('theme.selling_page.hero_point_1') }}</li>
            <li><i class="fa fa-check-circle"></i> {{ trans('theme.selling_page.hero_point_2') }}</li>
            <li><i class="fa fa-check-circle"></i> {{ trans('theme.selling_page.hero_point_3') }}</li>
          </ul>
          <a href="{{ route('selling') }}" class="sf-sell-auth__back">
            <i class="fa fa-arrow-left"></i> {{ trans('theme.selling_page.back_to_selling') }}
          </a>
        </div>

        <div class="sf-sell-auth__panel">
          <div id="sfSellAuthAlert"></div>

          @if ($errors->has('errors'))
            <div class="sf-sell-alert sf-sell-alert--danger" role="alert">
              <strong>{{ trans('app.error') }}!</strong> {{ $errors->first('errors') }}
            </div>
          @elseif ($errors->any())
            <div class="sf-sell-alert sf-sell-alert--danger" role="alert">
              <strong>{{ trans('app.error') }}!</strong> {{ trans('theme.selling_page.fix_highlighted_fields') }}
            </div>
          @endif

          @if (Session::has('success'))
            <div class="sf-sell-alert sf-sell-alert--success">{{ Session::get('success') }}</div>
          @endif

          @if (Session::has('error'))
            <div class="sf-sell-alert sf-sell-alert--danger" role="alert">{{ Session::get('error') }}</div>
          @endif

          @if (Session::has('message'))
            <div class="sf-sell-alert sf-sell-alert--success">{{ Session::get('message') }}</div>
          @endif

          @yield('content')
        </div>
      </div>
    </div>
  </section>

  <footer class="sf-sell-footer sf-sell-footer--compact">
    <div class="container text-center">
      <span>© {{ date('Y') }} {{ get_platform_title() }}</span>
    </div>
  </footer>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
  <script src="{{ selling_theme_asset_url('js/jqBootstrapValidation.min.js') }}"></script>
  <script src="{{ selling_theme_asset_url('js/app.js') }}?v={{ @filemtime(selling_theme_assets_path().'/js/app.js') ?: time() }}"></script>
  @yield('scripts')
</body>

</html>
