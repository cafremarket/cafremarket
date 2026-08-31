@extends('auth.master')

@section('content')
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
            <p class="admin-auth-card__subtitle">{{ get_site_title() }}</p>

            {!! Form::open(['url' => 'password/reset', 'id' => 'form', 'data-toggle' => 'validator', 'class' => 'admin-auth-form']) !!}
            {!! Form::hidden('token', $token) !!}

            <div class="form-group has-feedback">
              {!! Form::email('email', null, ['class' => 'form-control input-lg', 'placeholder' => trans('app.placeholder.valid_email'), 'required']) !!}
              <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
              <div class="help-block with-errors"></div>
            </div>

            <div class="form-group has-feedback">
              {!! Form::password('password', ['class' => 'form-control input-lg', 'id' => 'password', 'placeholder' => trans('app.placeholder.password'), 'data-minlength' => '6', 'required']) !!}
              <span class="glyphicon glyphicon-lock form-control-feedback"></span>
              <div class="help-block with-errors"></div>
            </div>

            <div class="form-group has-feedback">
              {!! Form::password('password_confirmation', ['class' => 'form-control input-lg', 'placeholder' => trans('app.placeholder.confirm_password'), 'data-match' => '#password', 'required']) !!}
              <span class="glyphicon glyphicon-lock form-control-feedback"></span>
              <div class="help-block with-errors"></div>
            </div>

            {!! Form::submit(trans('app.form.password_reset'), ['class' => 'btn btn-block btn-lg btn-flat btn-new admin-auth-form__submit']) !!}
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
