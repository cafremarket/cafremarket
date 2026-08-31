@extends('layouts.register')

@section('content')
  <div class="row">
    <div class="col-lg-12 text-center">
      <h2 class="section-heading">{{ trans('app.form.register_as_merchant') }}</h2>
      <h3 class="section-subheading text-muted">{{ trans('messages.merchant_benefits') }}</h3>
      <p class="text-muted seller-register-next-steps">{{ trans('messages.seller_register_next_steps') }}</p>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8 col-lg-offset-2 col-md-10 col-md-offset-1">
      <div class="seller-register-card">
        {!! Form::open([
          'route' => 'selling.register.submit',
          'id' => 'seller-registration-form',
          'data-toggle' => 'validator',
          'files' => true,
        ]) !!}

        @if (is_subscription_enabled())
          <div class="form-group">
            <label for="plans">{{ trans('theme.pricing') }}</label>
            {{ Form::select('plan', $plans, old('plan', $plan ?? null), ['id' => 'plans', 'class' => 'form-control', 'required']) }}
            @if ((bool) config('system_settings.trial_days'))
              <p class="help-block">{{ trans('help.charge_after_trial_days', ['days' => config('system_settings.trial_days')]) }}</p>
            @endif
          </div>
        @endif

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>{{ trans('theme.placeholder.full_name') }}</label>
              {!! Form::text('name', old('name'), ['class' => 'form-control', 'placeholder' => trans('theme.placeholder.full_name'), 'required']) !!}
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>{{ trans('app.placeholder.shop_name') }}</label>
              {!! Form::text('shop_name', old('shop_name'), ['class' => 'form-control', 'id' => 'shop_name', 'placeholder' => trans('app.placeholder.shop_name'), 'required']) !!}
            </div>
          </div>
        </div>

        <div class="form-group">
          <label>{{ trans('app.slug') }}</label>
          {!! Form::text('slug', old('slug'), ['class' => 'form-control slug', 'id' => 'shop_slug', 'placeholder' => trans('app.placeholder.slug'), 'pattern' => '[a-z0-9-]+']) !!}
          <p class="help-block">{{ trans('help.shop_url') }} — {{ url('/shop') }}/<span id="shop_slug_preview">{{ old('slug', 'your-store') }}</span></p>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>{{ trans('app.placeholder.valid_email') }}</label>
              {!! Form::email('email', old('email'), ['class' => 'form-control', 'placeholder' => trans('app.placeholder.valid_email'), 'required']) !!}
            </div>
          </div>
          <div class="col-md-6">
            @if (is_incevio_package_loaded('otp-login'))
              @include('otp-login::phone_field')
            @else
              <div class="form-group">
                <label>{{ trans('app.placeholder.phone') }}</label>
                {!! Form::text('phone', old('phone'), ['class' => 'form-control', 'placeholder' => trans('app.placeholder.phone')]) !!}
              </div>
            @endif
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>{{ trans('app.placeholder.password') }}</label>
              {!! Form::password('password', ['class' => 'form-control', 'id' => 'password', 'placeholder' => trans('app.placeholder.password'), 'data-minlength' => '6', 'required']) !!}
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>{{ trans('app.placeholder.confirm_password') }}</label>
              {!! Form::password('password_confirmation', ['class' => 'form-control', 'placeholder' => trans('app.placeholder.confirm_password'), 'data-match' => '#password', 'required']) !!}
            </div>
          </div>
        </div>

        @if (\App\Models\SystemConfig::vendorRegistrationHasAdditionalFields())
          @include('smartForm::partials._parsed_input_fields', ['row' => smart_form_fields(config('system_settings.smart_form_id_for_vendor_additional_info'))])
        @endif

        @if (config('services.recaptcha.key'))
          <div class="form-group">
            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.key') }}"></div>
          </div>
        @endif

        @if (config('system_settings.show_vendor_terms_and_conditions'))
          <div class="form-group">
            <label>
              {!! Form::checkbox('agree', 1, old('agree'), ['required']) !!}
              {!! trans('app.form.i_agree_with_merchant_terms', ['url' => route('page.open', \App\Models\Page::PAGE_TNC_FOR_MERCHANT)]) !!}
            </label>
          </div>
        @endif

        <div class="text-center" style="margin-top: 24px;">
          {!! Form::submit(trans('app.form.register'), ['class' => 'btn btn-primary btn-xl']) !!}
        </div>

        {!! Form::close() !!}

        <div class="seller-register-links">
          <a href="{{ route('selling') }}"><i class="fa fa-arrow-left"></i> {{ trans('app.back') }}</a>
          <a href="{{ route('selling.login') }}"><i class="fa fa-sign-in"></i> {{ trans('app.login') }}</a>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  @if (config('services.recaptcha.key'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  @endif

  @if (is_incevio_package_loaded('otp-login'))
    @include('otp-login::scripts')
  @endif

  <script>
    (function() {
      function slugify(text) {
        return text.toString().toLowerCase().trim()
          .replace(/[^\w\s-]/g, '')
          .replace(/[\s_-]+/g, '-')
          .replace(/^-+|-+$/g, '');
      }

      var shopName = document.getElementById('shop_name');
      var slugInput = document.getElementById('shop_slug');
      var slugPreview = document.getElementById('shop_slug_preview');
      var slugTouched = {{ old('slug') ? 'true' : 'false' }};

      function updatePreview() {
        if (slugPreview) {
          slugPreview.textContent = slugInput && slugInput.value ? slugInput.value : 'your-store';
        }
      }

      if (shopName && slugInput) {
        shopName.addEventListener('input', function() {
          if (!slugTouched) {
            slugInput.value = slugify(shopName.value);
            updatePreview();
          }
        });

        slugInput.addEventListener('input', function() {
          slugTouched = true;
          slugInput.value = slugify(slugInput.value);
          updatePreview();
        });
      }

      updatePreview();
    })();
  </script>
@endsection
