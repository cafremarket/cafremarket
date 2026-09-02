<div class="sf-wallet-page">
  <div class="sf-dashboard-welcome">
    <div class="sf-dashboard-welcome__top">
      <div class="sf-dashboard-welcome__greeting">
        <p class="sf-dashboard-welcome__eyebrow">@lang('packages.wallet.my_wallet')</p>
        <h2>@lang('packages.wallet.deposit_fund')</h2>
        <p>@lang('packages.wallet.available_balance'): {{ get_formated_currency($customer->balance, 2) }}</p>
      </div>

      <div class="sf-wallet-actions">
        <a href="{{ route('customer.account.wallet') }}" class="btn btn-default btn-sm">
          <i class="fas fa-arrow-left" aria-hidden="true"></i> @lang('packages.wallet.my_wallet')
        </a>
      </div>
    </div>
  </div>

  <div class="sf-wallet-deposit">
    {!! Form::open(['route' => 'customer.account.wallet.deposit', 'id' => 'depositForm', 'data-toggle' => 'validator']) !!}
    <div class="sf-wallet-deposit__grid">
      <div class="sf-wallet-deposit__panel">
        <h3 class="sf-wallet-deposit__panel-title">{{ trans('packages.wallet.amount') }}</h3>

        <div class="sf-form-group">
          <div class="input-group sf-input-group">
            @if (get_currency_prefix())
              <span class="input-group-addon">{{ get_currency_prefix() }}</span>
            @endif

            {!! Form::number('amount', null, ['id' => 'amount', 'class' => 'form-control sf-input input-lg', 'step' => 'any', 'min' => 1, 'placeholder' => trans('packages.wallet.amount'), 'required' => 'required']) !!}

            @if (get_currency_suffix())
              <span class="input-group-addon">{{ get_currency_suffix() }}</span>
            @endif
          </div>
          <div class="help-block with-errors"></div>
        </div>

        <h3 class="sf-wallet-deposit__panel-title">{{ trans('app.payment_method') ?? trans('app.select_payment_option') }}</h3>

        @foreach ($paymentMethods as $paymentMethod)
          @php
            $config = get_payment_config_info($paymentMethod->code);
          @endphp

          @continue(!$config)

          @if ($customer && $paymentMethod->code == 'stripe' && $customer->hasBillingToken())
            <div class="sf-form-group">
              <label>
                <input name="payment_method" value="saved_card" class="icheck payment-option" id="saved-card" type="radio" data-code="{{ $paymentMethod->code }}" data-info="{{ $config['msg'] }}" data-type="{{ $paymentMethod->type }}" required="required" {{ old('payment_method') ? '' : 'checked' }} />
                @lang('app.saved_card'): <i class="fa fa-cc-{{ strtolower($customer->pm_type) }}"></i> ************{{ $customer->pm_last_four }}
              </label>
            </div>
          @endif

          <div class="sf-form-group">
            <label>
              <input name="payment_method" value="{{ $paymentMethod->code }}" class="icheck payment-option" type="radio" data-code="{{ $paymentMethod->code }}" data-info="{{ $config['msg'] }}" data-type="{{ $paymentMethod->type }}" required="required" {{ old('payment_method') == $paymentMethod->code ? 'checked' : '' }} /> {{ $paymentMethod->code == 'stripe' ? trans('app.credit_card') : $paymentMethod->name }}
            </label>
          </div>
        @endforeach

        @php
          $has_mpesa = is_incevio_package_loaded('mpesa') && $paymentMethods->contains('code', 'mpesa');
          $has_emola = $paymentMethods->contains('code', 'emola');
        @endphp

        @if ($has_mpesa)
          <div id="mpesa-form" class="sf-form-group mpesa-wallet-field" style="display: none;">
            <label for="mpesa-number-wallet" class="sf-form-label">{{ trans('mpesa::lang.mpesa_number') }} <span class="text-muted">({{ trans('packages.wallet.required_when_mpesa') }})</span></label>
            {!! Form::text('mpesa_number', old('mpesa_number'), ['id' => 'mpesa-number-wallet', 'class' => 'form-control sf-input mpesa-request-field', 'placeholder' => trans('mpesa::lang.mpesa_number')]) !!}
            <div class="help-block with-errors"></div>
          </div>
        @endif

        @include('wallet::partials.wallet_deposit_fee_box')

        @if ($has_emola)
          <div id="emola-form" class="sf-form-group emola-wallet-field" style="display: none;">
            <label for="emola-number-wallet" class="sf-form-label">{{ trans('theme.emola_number') }} <span class="text-muted">({{ trans('packages.wallet.required_when_emola') }})</span></label>
            {!! Form::text('emola_number', old('emola_number'), ['id' => 'emola-number-wallet', 'class' => 'form-control sf-input emola-request-field', 'placeholder' => trans('theme.emola_number_placeholder'), 'pattern' => '^(86|87)\d{7}$', 'maxlength' => 9]) !!}
            <p class="help-block small text-muted">{{ trans('theme.emola_number_help') }}</p>
            <div class="help-block with-errors"></div>
          </div>
        @endif
      </div>

      <div class="sf-wallet-deposit__panel">
        @include('partials.authorizenet_card_form')
        @include('partials.stripe_card_form')

        <p id="payment-instructions" class="text-info small">
          <i class="fas fa-info-circle" aria-hidden="true"></i>
          <span>@lang('app.select_payment_option')</span>
        </p>

        <div id="submit-btn-block">
          <button id="pay-now-btn" class="btn sf-btn-primary btn-lg btn-block" type="submit">
            <i class="fas fa-shield-alt" aria-hidden="true"></i>
            <span id="pay-now-btn-txt">@lang('packages.wallet.pay_now')</span>
          </button>

          <a href="javascript:void(0)" id="paypal-express-btn" class="hide" type="submit">
            <img src="{{ asset(sys_image_path('payment-methods') . 'paypal-express.png') }}" width="70%" alt="paypal express checkout" title="paypal-express" />
          </a>
        </div>
      </div>
    </div>
    {!! Form::close() !!}
  </div>
</div>
