@extends('auth.master')

@section('content')
  @if (is_incevio_package_loaded('otp-login'))
    @include('otp-login::admin_login')
  @else
    <div class="admin-auth-card">
      <div class="login-section">
        <div class="form-container admin-auth-card__inner">
          <div class="image-holder admin-auth-card__visual"></div>
          <div class="login-form-section admin-auth-card__form">
            <div class="login-logo admin-auth-card__logo">
              <a href="{{ url('/') }}">
                <img src="{{ get_logo_url('system', 'logo') }}" class="brand-logo" height="47" alt="{{ trans('theme.logo') }}">
              </a>
            </div>

            <div class="form-section">
              <h3 class="admin-auth-card__title">{{ trans('app.login') }}</h3>
              <p class="admin-auth-card__subtitle">{{ get_site_title() }}</p>

              {!! Form::open(['route' => 'login', 'id' => 'form', 'data-toggle' => 'validator', 'class' => 'admin-auth-form']) !!}
              <div class="form-group has-feedback">
                {!! Form::email('email', null, ['id' => 'email', 'class' => 'form-control input-lg', 'placeholder' => trans('app.form.email_address'), 'required']) !!}
                <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                <div class="help-block with-errors"></div>
              </div>

              <div class="form-group has-feedback">
                {!! Form::password('password', ['class' => 'form-control input-lg', 'placeholder' => trans('app.form.password'), 'data-minlength' => '6', 'required']) !!}
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                <div class="help-block with-errors"></div>
              </div>

              <div class="row admin-auth-form__meta">
                <div class="col-sm-6">
                  <label class="admin-auth-form__remember">
                    {!! Form::checkbox('remember', null, null, ['class' => 'icheck']) !!} {{ trans('app.form.remember_me') }}
                  </label>
                </div>
                <div class="col-sm-6 text-right">
                  @unless (is_incevio_package_loaded('otp-login'))
                    <a class="admin-auth-form__forgot" href="{{ route('password.request') }}">{{ trans('app.form.forgot_password') }}</a>
                  @endunless
                </div>
              </div>

              {!! Form::submit(trans('app.form.login'), ['class' => 'btn btn-block btn-lg btn-flat btn-new admin-auth-form__submit']) !!}
              {!! Form::close() !!}

              <a class="admin-auth-form__register" href="{{ route('vendor.register') }}">
                <i class="fa fa-laptop"></i>
                {{ customer_can_register() ? trans('app.form.register_as_merchant') : trans('app.form.register') }}
              </a>
            </div>

            @include('partials._demo_admin_login')
          </div>
        </div>
      </div>
    </div>
  @endif
@endsection
