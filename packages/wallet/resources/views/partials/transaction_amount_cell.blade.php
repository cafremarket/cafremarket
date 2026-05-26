@if ($transaction->showsDepositPaymentBreakdown())
  <div>
    <small class="text-muted">{{ trans('packages.wallet.wallet_topup_credit_amount') }}:</small>
    <strong>{{ get_formated_currency($transaction->depositWalletCredit(), 2, config('system_settings.currency.id')) }}</strong>
  </div>
  <div class="mt-1">
    <small class="text-muted">{{ trans('packages.wallet.wallet_topup_total_charge') }}:</small>
    <strong>{{ get_formated_currency($transaction->depositPaymentTotal(), 2, config('system_settings.currency.id')) }}</strong>
  </div>
@else
  {{ get_formated_currency($transaction->amount, 2, config('system_settings.currency.id')) }}
@endif
