@php
  $walletFeePreviewUrl = Auth::guard('customer')->check()
      ? url('account/wallet/deposit/platform-fee')
      : url('wallet/deposit/platform-fee');
@endphp
<div id="wallet-topup-fee-box" class="alert alert-info" style="display: none;" data-fee-url="{{ $walletFeePreviewUrl }}">
  <strong>{{ trans('packages.wallet.wallet_topup_fee_summary') }}</strong>
  <table class="table table-condensed mb-0 mt-2" style="background: transparent;">
    <tr>
      <td>{{ trans('packages.wallet.wallet_topup_credit_amount') }}</td>
      <td class="text-right" id="wallet-fee-base">—</td>
    </tr>
    <tr id="wallet-fee-row" style="display: none;">
      <td>{{ trans('packages.wallet.wallet_topup_platform_fee') }}</td>
      <td class="text-right" id="wallet-fee-amount">—</td>
    </tr>
    <tr class="active">
      <td><strong>{{ trans('packages.wallet.wallet_topup_total_charge') }}</strong></td>
      <td class="text-right"><strong id="wallet-fee-total">—</strong></td>
    </tr>
  </table>
  <p class="small text-muted mb-0">{{ trans('packages.wallet.wallet_topup_fee_help') }}</p>
</div>
