@php
    $order = $order ?? ($data->order ?? $data);
    $invoiceTableColumns = (int) ($invoiceTableColumns ?? 4);
    $paymentCode = strtolower(trim((string) optional($order->paymentMethod)->code));
    $isMobilePayment = in_array($paymentCode, ['mpesa', 'emola'], true);
    $settlement = get_vendor_settlement_for_order($order);
    $marketplaceCommission = (float) ($settlement['marketplace_commission'] ?? 0);
    $affiliateCommission = (float) ($settlement['affiliate_commission'] ?? 0);
    $vendorNet = (float) ($settlement['net'] ?? 0);
    $showMarketplace = $isMobilePayment && $marketplaceCommission > 0;
    $showAffiliate = $affiliateCommission > 0;
    $feeRows = [];
    if ($showMarketplace) {
        $feeRows[] = ['label' => __('invoice.marketplace_commission'), 'amount' => $marketplaceCommission];
    }
    if ($showAffiliate) {
        $feeRows[] = ['label' => __('invoice.affiliate_commission'), 'amount' => $affiliateCommission];
    }
@endphp
@foreach ($feeRows as $feeRow)
  <tr>
    @if ($invoiceTableColumns === 2)
      <td>{{ $feeRow['label'] }}</td>
      <td style="text-align: right;">{{ get_formated_currency($feeRow['amount'], 2, $order->currency_id) }}</td>
    @elseif ($invoiceTableColumns === 3)
      <td colspan="2">{{ $feeRow['label'] }}</td>
      <td>{{ get_formated_currency($feeRow['amount'], 2, $order->currency_id) }}</td>
    @else
      <td colspan="2" style="background-color: white;"></td>
      <td>{{ $feeRow['label'] }}</td>
      <td>{{ get_formated_currency($feeRow['amount'], 2, $order->currency_id) }}</td>
    @endif
  </tr>
@endforeach
@if (count($feeRows) > 0)
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
