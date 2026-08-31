@extends('layouts.register')

@section('content')
  <div class="row">
    <div class="col-lg-12 text-center">
      <h2 class="section-heading">{{ trans('app.form.login') }}</h2>
      <h3 class="section-subheading text-muted">{{ trans('messages.seller_login_subtitle') }}</h3>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-6 col-lg-offset-3 col-md-8 col-md-offset-2">
      <div class="seller-register-card">
        {!! Form::open(['route' => 'login', 'id' => 'seller-login-form', 'data-toggle' => 'validator']) !!}
          <div class="form-group">
            {!! Form::label('email', trans('app.form.email_address')) !!}
            {!! Form::email('email', old('email'), ['class' => 'form-control', 'placeholder' => trans('app.placeholder.valid_email'), 'required', 'autofocus']) !!}
          </div>

          <div class="form-group">
            {!! Form::label('password', trans('app.form.password')) !!}
            {!! Form::password('password', ['class' => 'form-control', 'placeholder' => trans('app.form.password'), 'data-minlength' => '6', 'required']) !!}
          </div>

          <div class="form-group">
            <label>
              {!! Form::checkbox('remember', null, null, ['class' => 'icheck']) !!} {{ trans('app.form.remember_me') }}
            </label>
          </div>

          <div class="text-center">
            {!! Form::submit(trans('app.form.login'), ['class' => 'btn btn-primary btn-xl']) !!}
          </div>
        {!! Form::close() !!}

        <div class="seller-register-links">
          <a href="{{ route('password.request') }}">{{ trans('app.form.forgot_password') }}</a>
          <a href="{{ route('selling.register') }}">{{ trans('app.form.register_as_merchant') }}</a>
        </div>
      </div>
    </div>
  </div>
@endsection
