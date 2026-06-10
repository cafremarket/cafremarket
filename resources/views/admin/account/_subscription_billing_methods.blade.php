@php
  $paymentMethods = get_subscription_payment_methods();
  $walletBalance = 0;
  try {
      $walletBalance = (float) (Auth::user()->shop->balance ?? 0);
  } catch (\Throwable $e) {
      $walletBalance = 0;
  }
@endphp

<div class="alert alert-info">
  <strong><i class="icon fa fa-info-circle"></i></strong>
  {{ trans('messages.subscription_choose_payment') }}
</div>

<p class="lead text-center">
  {{ trans('app.wallet') }}:
  <strong>{{ get_formated_currency($walletBalance, 2, config('system_settings.currency.id')) }}</strong>
</p>

<div class="list-group">
  @foreach ($paymentMethods as $method)
    <div class="list-group-item">
      <i class="fa fa-{{ $method['code'] === 'wallet' ? 'wallet' : 'mobile' }}"></i>
      <strong>{{ $method['name'] }}</strong>
      @if ($method['code'] === 'wallet')
        <span class="text-muted small"> — {{ trans('messages.wallet_subscription_billing') }}</span>
      @endif
    </div>
  @endforeach
</div>

@if (is_incevio_package_loaded('wallet') && Route::has('merchant.wallet.deposit.form'))
  <p class="text-center spacer10">
    <a href="{{ route('merchant.wallet.deposit.form') }}" class="btn btn-default">
      <i class="fa fa-plus-circle"></i> {{ trans('packages.wallet.deposit_fund') }}
    </a>
  </p>
@endif
