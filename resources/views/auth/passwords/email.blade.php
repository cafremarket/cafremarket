@extends('auth.master')

@section('content')
  @if (session('status'))
    <div class="alert alert-success admin-auth-alert">
      {{ session('status') }}
    </div>
  @endif

  <div class="admin-auth-card">
    <div class="login-section">
      <div class="form-container admin-auth-card__inner">
        <div class="image-holder admin-auth-card__visual"></div>
        <div class="login-form-section admin-auth-card__form">
          <div class="login-logo admin-auth-card__logo">
            <a href="{{ url('/') }}">
              <img src="{{ get_logo_url('system', 'full') }}" class="brand-logo" height="47" alt="{{ trans('theme.logo') }}">
            </a>
          </div>

          <div class="form-section">
            <h3 class="admin-auth-card__title">{{ trans('app.form.password_reset') }}</h3>
            <p class="admin-auth-card__subtitle">{{ trans('app.form.forgot_password') }}</p>

            {!! Form::open(['route' => 'password.email', 'id' => 'form', 'data-toggle' => 'validator', 'class' => 'admin-auth-form']) !!}
            <div class="form-group has-feedback">
              {!! Form::email('email', null, ['class' => 'form-control input-lg', 'placeholder' => trans('app.placeholder.valid_email'), 'required']) !!}
              <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
              <div class="help-block with-errors"></div>
            </div>

            {!! Form::submit(trans('app.form.send_password_link'), ['class' => 'btn btn-block btn-lg btn-flat btn-new admin-auth-form__submit']) !!}
            {!! Form::close() !!}

            <a class="admin-auth-form__register" href="{{ route('login') }}">
              <i class="fa fa-arrow-left"></i> {{ trans('app.login') }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
