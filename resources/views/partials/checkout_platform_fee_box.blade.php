<div id="checkout-platform-fee-box" class="alert alert-info" style="display: none;" data-fee-url="{{ url('wallet/checkout/platform-fee') }}">
  <strong>{{ trans('packages.wallet.checkout_payment_fee_summary') }}</strong>
  <table class="table table-condensed mb-0 mt-2" style="background: transparent;">
    <tr>
      <td>{{ trans('packages.wallet.checkout_order_total') }}</td>
      <td class="text-right" id="checkout-fee-base">—</td>
    </tr>
    <tr id="checkout-fee-row" style="display: none;">
      <td>{{ trans('packages.wallet.checkout_customer_platform_fee') }}</td>
      <td class="text-right" id="checkout-fee-amount">—</td>
    </tr>
    <tr class="active">
      <td><strong>{{ trans('packages.wallet.checkout_you_will_pay') }}</strong></td>
      <td class="text-right"><strong id="checkout-fee-total">—</strong></td>
    </tr>
  </table>
  <p class="small text-muted mb-0">{{ trans('packages.wallet.checkout_platform_fee_help_customer') }}</p>
</div>
