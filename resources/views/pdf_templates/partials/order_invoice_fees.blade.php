@php
    $order = $order ?? ($data->order ?? $data);
    $invoiceTableColumns = (int) ($invoiceTableColumns ?? 4);
    $paymentCode = strtolower(trim((string) optional($order->paymentMethod)->code));
    $isMobilePayment = in_array($paymentCode, ['mpesa', 'emola'], true);
    $subscriptionFee = (float) ($order->subscription_transaction_fee ?? 0);
    $platformFee = (float) ($order->platform_payment_fee ?? 0);
    $customerTransactionFee = $subscriptionFee + $platformFee;
    $customerTotalPaid = round((float) $order->grand_total + $customerTransactionFee, 2);
    $settlement = $isMobilePayment ? get_vendor_settlement_for_order($order) : null;
    $vendorCommission = $settlement ? (float) ($settlement['total_deductions'] ?? 0) : 0;
    $vendorNet = $settlement ? (float) ($settlement['net'] ?? 0) : 0;
@endphp
@if ($isMobilePayment && $customerTransactionFee > 0)
  <tr>
    @if ($invoiceTableColumns === 3)
      <td colspan="2">@lang('invoice.transaction_fee')</td>
      <td>{{ get_formated_currency($customerTransactionFee, 2) }}</td>
    @else
      <td colspan="2" style="background-color: white;"></td>
      <td>@lang('invoice.transaction_fee')</td>
      <td>{{ get_formated_currency($customerTransactionFee, 2) }}</td>
    @endif
  </tr>
  <tr>
    @if ($invoiceTableColumns === 3)
      <td colspan="2" style="background: #e6f2ff"><strong>@lang('invoice.total_paid_mobile')</strong></td>
      <td style="background: #e6f2ff"><strong>{{ get_formated_currency($customerTotalPaid, 2) }}</strong></td>
    @else
      <td colspan="2" style="background-color: white;"></td>
      <td style="background: #e6f2ff">@lang('invoice.total_paid_mobile')</td>
      <td style="background: #e6f2ff">{{ get_formated_currency($customerTotalPaid, 2) }}</td>
    @endif
  </tr>
@endif
@if ($isMobilePayment && $vendorCommission > 0)
  <tr>
    @if ($invoiceTableColumns === 3)
      <td colspan="2">@lang('invoice.marketplace_commission')</td>
      <td>{{ get_formated_currency($vendorCommission, 2) }}</td>
    @else
      <td colspan="2" style="background-color: white;"></td>
      <td>@lang('invoice.marketplace_commission')</td>
      <td>{{ get_formated_currency($vendorCommission, 2) }}</td>
    @endif
  </tr>
  <tr>
    @if ($invoiceTableColumns === 3)
      <td colspan="2">@lang('invoice.vendor_net')</td>
      <td>{{ get_formated_currency($vendorNet, 2) }}</td>
    @else
      <td colspan="2" style="background-color: white;"></td>
      <td>@lang('invoice.vendor_net')</td>
      <td>{{ get_formated_currency($vendorNet, 2) }}</td>
    @endif
  </tr>
@endif
