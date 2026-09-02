<div class="sf-wallet-page">
  <div class="sf-dashboard-welcome">
    <div class="sf-dashboard-welcome__top">
      <div class="sf-dashboard-welcome__greeting">
        <p class="sf-dashboard-welcome__eyebrow">@lang('packages.wallet.my_wallet')</p>
        <h2>@lang('packages.wallet.transfer_self_merchant')</h2>
        <p>@lang('packages.wallet.max_transfer_amount', ['amount' => get_formated_currency($wallet->balance, 2)])</p>
      </div>

      <div class="sf-wallet-actions">
        <a href="{{ route('customer.account.wallet') }}" class="btn btn-default btn-sm">
          <i class="fas fa-arrow-left" aria-hidden="true"></i> @lang('packages.wallet.my_wallet')
        </a>
      </div>
    </div>
  </div>

  <div class="sf-form-panel" style="max-width: 640px; margin: 0 auto;">
    {!! Form::open(['route' => 'customer.account.wallet.transfer', 'id' => 'form', 'data-toggle' => 'validator', 'class' => 'sf-form']) !!}

    <div class="sf-form-group">
      {!! Form::label('order', trans('packages.wallet.amount'), ['class' => 'sf-form-label']) !!}
      <div class="input-group sf-input-group">
        @if (get_currency_prefix())
          <span class="input-group-addon">{{ get_currency_prefix() }}</span>
        @endif

        {!! Form::number('amount', null, ['class' => 'form-control sf-input', 'step' => 'any', 'placeholder' => trans('packages.wallet.amount'), 'max' => $wallet->balance, 'required']) !!}

        @if (get_currency_suffix())
          <span class="input-group-addon">{{ get_currency_suffix() }}</span>
        @endif
      </div>
      <div class="help-block with-errors">{{ trans('packages.wallet.max_transfer_amount', ['amount' => get_formated_currency($wallet->balance, 2)]) }}</div>
    </div>

    <button id="pay-now-btn" class="btn sf-btn-primary btn-lg btn-block" type="submit">
      <i class="fas fa-shield-alt" aria-hidden="true"></i>
      <span id="pay-now-btn-txt">@lang('packages.wallet.transfer_self_merchant')</span>
    </button>
    {!! Form::close() !!}
  </div>
</div>
