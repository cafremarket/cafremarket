@extends('affiliate::auth_layout')

@section('content')
  <div class="box">
    <div class="login-section">
      <div class="form-container">
        <div class="image-holder-affiliate"></div>
        <div class="login-form-section">
          <div class="login-logo">
            <a href="{{ url('/') }}">
              <img src="{{ get_logo_url('system', 'full') }}" class="brand-logo" height="47px" alt="{{ trans('theme.logo') }}" title="{{ trans('theme.logo') }}">
            </a>
          </div>

          <div class="form-section">
            <h3 class="text-center mt-0">{{ trans('packages.affiliate.affiliate_register') }}</h3>
            {!! Form::open(['route' => 'affiliate.register', 'id' => 'form', 'data-toggle' => 'validator', 'files' => true]) !!}
            <div class="form-group has-feedback">
              {!! Form::text('name', null, ['class' => 'form-control input-lg', 'placeholder' => trans('theme.placeholder.full_name'), 'required']) !!}
              <span class="glyphicon glyphicon-user form-control-feedback"></span>
              <div class="help-block with-errors"></div>
            </div>

            <div class="form-group has-feedback">
              {!! Form::text('username', null, ['class' => 'form-control input-lg', 'id' => 'js-username', 'placeholder' => trans('packages.affiliate.placeholder_username'), 'required']) !!}
              <span class="glyphicon glyphicon-user form-control-feedback"></span>
              <div class="help-block with-errors"><span id="js-username-feedback"></span></div>
            </div>

            <div class="form-group has-feedback">
              {!! Form::email('email', null, ['class' => 'form-control input-lg', 'placeholder' => trans('theme.placeholder.valid_email'), 'required']) !!}
              <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
              <div class="help-block with-errors"></div>
            </div>

            <div class="form-group has-feedback">
              {!! Form::password('password', ['class' => 'form-control input-lg', 'id' => 'password', 'placeholder' => trans('theme.placeholder.password'), 'data-minlength' => '6', 'required']) !!}
              <span class="glyphicon glyphicon-lock form-control-feedback"></span>
              <div class="help-block with-errors"></div>
            </div>

            <div class="form-group has-feedback">
              {!! Form::password('password_confirmation', ['class' => 'form-control input-lg', 'placeholder' => trans('theme.placeholder.confirm_password'), 'data-match' => '#password', 'required']) !!}
              <span class="glyphicon glyphicon-lock form-control-feedback"></span>
              <div class="help-block with-errors"></div>
            </div>

            <div class="form-group has-feedback">
              @if (config('services.recaptcha.key'))
                <div class="g-recaptcha" data-sitekey="{!! config('services.recaptcha.key') !!}"></div>
              @endif
              <div class="help-block with-errors"></div>
            </div>

            <div class="row">
              <div class="col-sm-7">
                <div class="form-group">
                  <label for="terms_and_conditions">
                    {!! Form::checkbox('agree', null, null, ['class' => 'icheck', 'id' => 'terms_and_conditions', 'required']) !!} {!! trans('theme.input_label.i_agree_with_terms') !!}
                  </label>
                  <div class="help-block with-errors"></div>
                </div>
              </div>
              <div class="col-sm-5">
                {!! Form::submit(trans('theme.register'), ['class' => 'btn btn-block btn-lg btn-flat btn-primary']) !!}
              </div>
            </div>
            {!! Form::close() !!}
            <a href="{{ route('affiliate.login.form') }}" class="btn btn-link">{{ trans('theme.have_an_account') }}</a>
          </div> <!-- /.form-section -->
        </div> <!-- /.login-form-section -->
      </div> <!-- /.form-container -->
    </div> <!-- /.login-section -->
  </div> <!-- /.box -->
@endsection

@section('scripts')
  @include('affiliate::scripts.affiliate_username_validation')
@endsection
