@extends('layouts.auth')

@section('page_title', trans('app.form.login'))
@section('auth_heading', trans('app.form.login'))
@section('auth_subheading', trans('messages.seller_login_subtitle'))

@section('content')
  {!! Form::open(['route' => 'login', 'id' => 'seller-login-form', 'class' => 'sf-sell-auth__form']) !!}
    {!! Form::hidden('_store_login', 1) !!}

    <div class="sf-sell-form-group {{ $errors->has('email') ? 'sf-sell-form-group--invalid' : '' }}">
      {!! Form::label('email', trans('app.form.email_address')) !!}
      {!! Form::email('email', old('email'), ['class' => 'sf-sell-form-control' . ($errors->has('email') ? ' is-invalid' : ''), 'placeholder' => trans('app.placeholder.valid_email'), 'required', 'autofocus', 'aria-invalid' => $errors->has('email') ? 'true' : 'false', 'aria-describedby' => $errors->has('email') ? 'error-email' : null]) !!}
      @include('partials._field_error', ['field' => 'email'])
    </div>

    <div class="sf-sell-form-group {{ $errors->has('password') ? 'sf-sell-form-group--invalid' : '' }}">
      {!! Form::label('password', trans('app.form.password')) !!}
      {!! Form::password('password', ['class' => 'sf-sell-form-control' . ($errors->has('password') ? ' is-invalid' : ''), 'placeholder' => trans('app.form.password'), 'required', 'minlength' => 6, 'aria-invalid' => $errors->has('password') ? 'true' : 'false', 'aria-describedby' => $errors->has('password') ? 'error-password' : null]) !!}
      @include('partials._field_error', ['field' => 'password'])
    </div>

    <div class="sf-sell-form-group sf-sell-form-group--inline">
      <label class="sf-sell-checkbox">
        {!! Form::checkbox('remember', null, null) !!}
        <span>{{ trans('app.form.remember_me') }}</span>
      </label>
    </div>

    <button type="submit" class="sf-sell-btn sf-sell-btn--primary sf-sell-btn--lg sf-sell-btn--block">
      {{ trans('app.form.login') }}
    </button>
  {!! Form::close() !!}

  <div class="sf-sell-auth__links">
    <a href="{{ route('password.request') }}">{{ trans('app.form.forgot_password') }}</a>
    <a href="{{ route('selling.register') }}">{{ trans('app.form.register_as_merchant') }}</a>
  </div>
@endsection

@section('scripts')
  <script>
    window.sfSellingAuth = {
      loginError: @json(trans('theme.selling_page.login_error')),
      dashboardUrl: @json(route('merchant.dashboard')),
      fixFieldsMsg: @json(trans('theme.selling_page.fix_highlighted_fields'))
    };
  </script>
  <script src="{{ selling_theme_asset_url('js/selling-auth.js') }}?v={{ @filemtime(selling_theme_assets_path().'/js/selling-auth.js') ?: time() }}"></script>
  @include('scripts.password_toggle')
@endsection
