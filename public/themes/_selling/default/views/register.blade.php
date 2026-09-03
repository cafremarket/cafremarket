@extends('layouts.auth')

@section('page_title', trans('app.form.register_as_merchant'))
@section('auth_heading', trans('app.form.register_as_merchant'))
@section('auth_subheading', trans('messages.seller_register_next_steps'))

@section('content')
  {!! Form::open([
    'route' => 'selling.register.submit',
    'id' => 'seller-registration-form',
    'class' => 'sf-sell-auth__form',
    'files' => true,
    'autocomplete' => 'on',
  ]) !!}
    @csrf

    <div class="sf-sell-form-group {{ $errors->has('plan') ? 'sf-sell-form-group--invalid' : '' }}" id="sfSellPlanGroup" @unless(is_subscription_enabled()) style="display:none" @endunless>
      {!! Form::label('plan', trans('theme.pricing')) !!}
      {{ Form::select('plan', $plans ?? [], old('plan', $plan ?? null), ['id' => 'plans', 'class' => 'sf-sell-form-control' . ($errors->has('plan') ? ' is-invalid' : ''), is_subscription_enabled() ? 'required' : null, 'aria-invalid' => $errors->has('plan') ? 'true' : 'false', 'aria-describedby' => $errors->has('plan') ? 'error-plan' : null]) }}
      @include('partials._field_error', ['field' => 'plan'])
      @if ((bool) config('system_settings.trial_days'))
        <p class="sf-sell-form-help">{{ trans('help.charge_after_trial_days', ['days' => config('system_settings.trial_days')]) }}</p>
      @endif
    </div>

    <div class="sf-sell-form-row">
      <div class="sf-sell-form-group {{ $errors->has('name') ? 'sf-sell-form-group--invalid' : '' }}">
        {!! Form::label('name', trans('theme.placeholder.full_name')) !!}
        {!! Form::text('name', old('name'), ['class' => 'sf-sell-form-control' . ($errors->has('name') ? ' is-invalid' : ''), 'placeholder' => trans('theme.placeholder.full_name'), 'required', 'aria-invalid' => $errors->has('name') ? 'true' : 'false', 'aria-describedby' => $errors->has('name') ? 'error-name' : null]) !!}
        @include('partials._field_error', ['field' => 'name'])
      </div>
      <div class="sf-sell-form-group {{ $errors->has('shop_name') ? 'sf-sell-form-group--invalid' : '' }}">
        {!! Form::label('shop_name', trans('app.placeholder.shop_name')) !!}
        {!! Form::text('shop_name', old('shop_name'), ['class' => 'sf-sell-form-control' . ($errors->has('shop_name') ? ' is-invalid' : ''), 'id' => 'shop_name', 'placeholder' => trans('app.placeholder.shop_name'), 'required', 'aria-invalid' => $errors->has('shop_name') ? 'true' : 'false', 'aria-describedby' => $errors->has('shop_name') ? 'error-shop_name' : null]) !!}
        @include('partials._field_error', ['field' => 'shop_name'])
      </div>
    </div>

    @php $selectedSellerType = old('seller_type', \App\Models\Shop::SELLER_TYPE_INDIVIDUAL); @endphp
    <div class="sf-sell-form-group {{ $errors->has('seller_type') ? 'sf-sell-form-group--invalid' : '' }}">
      <span class="sf-sell-type-label">{{ trans('app.form.seller_type') }} *</span>
      <div class="sf-sell-type-cards" role="radiogroup" aria-label="{{ trans('app.form.seller_type') }}">
        @foreach (\App\Models\Shop::sellerTypeOptions() as $value => $label)
          <label class="sf-sell-type-card {{ $selectedSellerType === $value ? 'is-selected' : '' }}">
            <input type="radio" name="seller_type" value="{{ $value }}" {{ $selectedSellerType === $value ? 'checked' : '' }} required>
            <span>
              <strong>{{ $label }}</strong>
              <small>{{ $value === \App\Models\Shop::SELLER_TYPE_INDIVIDUAL ? trans('help.seller_type_individual') : trans('help.seller_type_company') }}</small>
            </span>
          </label>
        @endforeach
      </div>
      @include('partials._field_error', ['field' => 'seller_type'])
    </div>

    <div class="sf-sell-form-group {{ $errors->has('nuit') ? 'sf-sell-form-group--invalid' : '' }}">
      {!! Form::label('nuit', trans('app.form.nuit') . ' *') !!}
      {!! Form::text('nuit', old('nuit'), ['class' => 'sf-sell-form-control' . ($errors->has('nuit') ? ' is-invalid' : ''), 'placeholder' => trans('app.placeholder.nuit'), 'required', 'maxlength' => 20, 'aria-invalid' => $errors->has('nuit') ? 'true' : 'false', 'aria-describedby' => $errors->has('nuit') ? 'error-nuit' : null]) !!}
      <p class="sf-sell-form-help">{{ trans('help.nuit') }}</p>
      @include('partials._field_error', ['field' => 'nuit'])
    </div>

    <div class="sf-sell-form-group {{ $errors->has('slug') ? 'sf-sell-form-group--invalid' : '' }}">
      {!! Form::label('slug', trans('app.slug')) !!}
      {!! Form::text('slug', old('slug'), ['class' => 'sf-sell-form-control slug' . ($errors->has('slug') ? ' is-invalid' : ''), 'id' => 'shop_slug', 'placeholder' => trans('app.placeholder.slug'), 'pattern' => '[a-z0-9-]+', 'aria-invalid' => $errors->has('slug') ? 'true' : 'false', 'aria-describedby' => $errors->has('slug') ? 'error-slug' : null]) !!}
      @include('partials._field_error', ['field' => 'slug'])
      <p class="sf-sell-form-help">{{ trans('help.shop_url') }} — {{ url('/shop') }}/<span id="shop_slug_preview">{{ old('slug', 'your-store') }}</span></p>
    </div>

    <div class="sf-sell-form-row">
      <div class="sf-sell-form-group {{ $errors->has('email') ? 'sf-sell-form-group--invalid' : '' }}">
        {!! Form::label('email', trans('app.placeholder.valid_email')) !!}
        {!! Form::email('email', old('email'), ['class' => 'sf-sell-form-control' . ($errors->has('email') ? ' is-invalid' : ''), 'placeholder' => trans('app.placeholder.valid_email'), 'required', 'aria-invalid' => $errors->has('email') ? 'true' : 'false', 'aria-describedby' => $errors->has('email') ? 'error-email' : null]) !!}
        @include('partials._field_error', ['field' => 'email'])
      </div>
      <div class="sf-sell-form-group {{ $errors->has('phone') ? 'sf-sell-form-group--invalid' : '' }}">
        @if (is_incevio_package_loaded('otp-login'))
          @include('otp-login::phone_field')
        @else
          {!! Form::label('phone', trans('app.placeholder.phone')) !!}
          {!! Form::text('phone', old('phone'), ['class' => 'sf-sell-form-control' . ($errors->has('phone') ? ' is-invalid' : ''), 'placeholder' => trans('app.placeholder.phone'), 'aria-invalid' => $errors->has('phone') ? 'true' : 'false', 'aria-describedby' => $errors->has('phone') ? 'error-phone' : null]) !!}
        @endif
        @include('partials._field_error', ['field' => 'phone'])
      </div>
    </div>

    <div class="sf-sell-form-row">
      <div class="sf-sell-form-group {{ $errors->has('password') ? 'sf-sell-form-group--invalid' : '' }}">
        {!! Form::label('password', trans('app.placeholder.password')) !!}
        {!! Form::password('password', ['class' => 'sf-sell-form-control' . ($errors->has('password') ? ' is-invalid' : ''), 'id' => 'password', 'placeholder' => trans('app.placeholder.password'), 'required', 'minlength' => 6, 'aria-invalid' => $errors->has('password') ? 'true' : 'false', 'aria-describedby' => $errors->has('password') ? 'error-password' : null]) !!}
        @include('partials._field_error', ['field' => 'password'])
      </div>
      <div class="sf-sell-form-group {{ $errors->has('password_confirmation') ? 'sf-sell-form-group--invalid' : '' }}">
        {!! Form::label('password_confirmation', trans('app.placeholder.confirm_password')) !!}
        {!! Form::password('password_confirmation', ['class' => 'sf-sell-form-control' . ($errors->has('password_confirmation') ? ' is-invalid' : ''), 'placeholder' => trans('app.placeholder.confirm_password'), 'required', 'aria-invalid' => $errors->has('password_confirmation') ? 'true' : 'false', 'aria-describedby' => $errors->has('password_confirmation') ? 'error-password_confirmation' : null]) !!}
        @include('partials._field_error', ['field' => 'password_confirmation'])
      </div>
    </div>

    @if (\App\Models\SystemConfig::vendorRegistrationHasAdditionalFields())
      <div class="sf-sell-form-group">
        @include('smartForm::partials._parsed_input_fields', ['row' => smart_form_fields(config('system_settings.smart_form_id_for_vendor_additional_info'))])
        @foreach ($errors->getMessages() as $field => $messages)
          @if (\Illuminate\Support\Str::startsWith($field, 'extra_info.'))
            @foreach ($messages as $message)
              <p class="sf-sell-field-error" role="alert"><i class="fa fa-exclamation-circle"></i> {{ $message }}</p>
            @endforeach
          @endif
        @endforeach
      </div>
    @endif

    @if (config('services.recaptcha.key'))
      <div class="sf-sell-form-group {{ $errors->has('g-recaptcha-response') ? 'sf-sell-form-group--invalid' : '' }}">
        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.key') }}"></div>
        @include('partials._field_error', ['field' => 'g-recaptcha-response'])
      </div>
    @endif

    @if (config('system_settings.show_vendor_terms_and_conditions'))
      <div class="sf-sell-form-group {{ $errors->has('agree') ? 'sf-sell-form-group--invalid' : '' }}">
        <label class="sf-sell-checkbox">
          {!! Form::checkbox('agree', 1, old('agree'), ['required', 'class' => $errors->has('agree') ? 'is-invalid' : '']) !!}
          <span>{!! trans('app.form.i_agree_with_merchant_terms', ['url' => route('page.open', \App\Models\Page::PAGE_TNC_FOR_MERCHANT)]) !!}</span>
        </label>
        @include('partials._field_error', ['field' => 'agree'])
      </div>
    @endif

    <button type="submit" class="sf-sell-btn sf-sell-btn--primary sf-sell-btn--lg sf-sell-btn--block" id="seller-register-submit">
      {{ trans('app.form.register') }}
    </button>
  {!! Form::close() !!}

  <div class="sf-sell-auth__links">
    <a href="{{ route('selling.login') }}">{{ trans('app.login') }}</a>
    <a href="{{ route('selling') }}">{{ trans('theme.selling_page.back_to_selling') }}</a>
  </div>
@endsection

@section('scripts')
  <script>
    window.sfSellingAuth = {
      plansUrl: @json(route('selling.api.subscription_plans')),
      vendorPlansUrl: @json(url('/api/vendor/data/subscription_plans')),
      selectedPlan: @json(old('plan', $plan ?? null)),
      freeLabel: @json(__('theme.free')),
      perMonthLabel: @json(trans('app.per_month')),
      loadError: @json(trans('theme.selling_page.api_load_error')),
      fixFieldsMsg: @json(trans('theme.selling_page.fix_highlighted_fields'))
    };
  </script>
  <script src="{{ selling_theme_asset_url('js/selling-auth.js') }}?v={{ @filemtime(selling_theme_assets_path().'/js/selling-auth.js') ?: time() }}"></script>

  @if (config('services.recaptcha.key'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  @endif

  @if (is_incevio_package_loaded('otp-login'))
    @include('otp-login::scripts')
  @endif

  @include('scripts.password_toggle')
  @include('scripts.google_place')

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

      document.querySelectorAll('input[name="seller_type"]').forEach(function(input) {
        input.addEventListener('change', function() {
          document.querySelectorAll('.sf-sell-type-card').forEach(function(card) {
            var radio = card.querySelector('input[name="seller_type"]');
            card.classList.toggle('is-selected', radio && radio.checked);
          });
        });
      });

      var registerForm = document.getElementById('seller-registration-form');
      var registerBtn = document.getElementById('seller-register-submit');
      if (registerForm && registerBtn) {
        registerForm.addEventListener('submit', function () {
          if (registerForm.dataset.submitting === '1') {
            return false;
          }
          registerForm.dataset.submitting = '1';
          registerBtn.disabled = true;
          registerBtn.setAttribute('aria-busy', 'true');
          registerBtn.textContent = @json(trans('messages.please_wait'));
        });
      }
    })();
  </script>
@endsection
