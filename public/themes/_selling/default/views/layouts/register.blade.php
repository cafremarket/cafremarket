<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, shrink-to-fit=no">
  <title>{{ trans('app.form.register_as_merchant') }} — {{ get_platform_title() }}</title>
  <link rel="icon" href="{{ get_icon_url('system', 'thumbnail') }}" type="image/x-icon" />

  <link href="{{ selling_theme_asset_url('css/vendor.css') }}" rel="stylesheet">
  <link href="{{ selling_theme_asset_url('css/agency.css') }}" rel="stylesheet">
  <link href="{{ selling_theme_asset_url('css/style.css') }}" rel="stylesheet">

  <style>
    .seller-register-page {
      padding: 120px 0 80px;
      background: #f8f9fa;
      min-height: 100vh;
    }

    .seller-register-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
      padding: 32px 28px;
      margin-bottom: 30px;
    }

    .seller-register-card .form-control {
      height: 46px;
    }

    .seller-register-card .btn-xl {
      min-width: 220px;
    }

    .seller-register-links {
      margin-top: 20px;
      text-align: center;
    }

    .seller-register-links a {
      margin: 0 10px;
      color: #333;
    }
  </style>
</head>

<body id="page-top">
  @include('nav.mainnav')

  <section class="seller-register-page">
    <div class="container">
      @if (count($errors) > 0)
        <div class="alert alert-danger">
          <strong>{{ trans('app.error') }}!</strong> {{ trans('messages.input_error') }}
          <ul class="list-unstyled" style="margin-top: 10px; margin-bottom: 0;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @if (Session::has('message'))
        <div class="alert alert-success">{{ Session::get('message') }}</div>
      @endif

      @yield('content')
    </div>
  </section>

  <footer class="page-footer font-small indigo pt-0">
    <div class="container text-center" style="padding: 20px 0;">
      <span class="copyright">© {{ date('Y') }} <a href="{{ url('/') }}">{{ get_platform_title() }}</a></span>
    </div>
  </footer>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
  <script src="{{ selling_theme_asset_url('js/jqBootstrapValidation.min.js') }}"></script>
  <script src="{{ selling_theme_asset_url('js/app.js') }}"></script>

  <script src="{{ selling_theme_asset_url('js/app.js') }}"></script>
  <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">

  @include('scripts.password_toggle')
  @include('scripts.google_place')

  @yield('scripts')
</body>

</html>
