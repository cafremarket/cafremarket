@php
    $order = $order ?? ($data->order ?? $data);
    $invoiceTableColumns = (int) ($invoiceTableColumns ?? 4);
    $paymentCode = strtolower(trim((string) optional($order->paymentMethod)->code));
    $isMobilePayment = in_array($paymentCode, ['mpesa', 'emola'], true);
    // Customer transaction fees are admin-only — never print on customer/merchant invoices.
    $settlement = $isMobilePayment ? get_vendor_settlement_for_order($order) : null;
    $vendorCommission = $settlement ? (float) ($settlement['total_deductions'] ?? 0) : 0;
    $vendorNet = $settlement ? (float) ($settlement['net'] ?? 0) : 0;
@endphp
@if ($isMobilePayment && $vendorCommission > 0)
  <tr>
    @if ($invoiceTableColumns === 2)
      <td>@lang('invoice.marketplace_commission')</td>
      <td style="text-align: right;">{{ get_formated_currency($vendorCommission, 2, $order->currency_id) }}</td>
    @elseif ($invoiceTableColumns === 3)
      <td colspan="2">@lang('invoice.marketplace_commission')</td>
      <td>{{ get_formated_currency($vendorCommission, 2, $order->currency_id) }}</td>
    @else
      <td colspan="2" style="background-color: white;"></td>
      <td>@lang('invoice.marketplace_commission')</td>
      <td>{{ get_formated_currency($vendorCommission, 2, $order->currency_id) }}</td>
    @endif
  </tr>
  <tr>
    @if ($invoiceTableColumns === 2)
      <td>@lang('invoice.vendor_net')</td>
      <td style="text-align: right;">{{ get_formated_currency($vendorNet, 2, $order->currency_id) }}</td>
    @elseif ($invoiceTableColumns === 3)
      <td colspan="2">@lang('invoice.vendor_net')</td>
      <td>{{ get_formated_currency($vendorNet, 2, $order->currency_id) }}</td>
    @else
      <td colspan="2" style="background-color: white;"></td>
      <td>@lang('invoice.vendor_net')</td>
      <td>{{ get_formated_currency($vendorNet, 2, $order->currency_id) }}</td>
    @endif
  </tr>
@endif
