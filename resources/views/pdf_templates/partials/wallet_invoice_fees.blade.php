@php
    $transaction = $transaction ?? ($data['transaction'] ?? $data);
    $meta = is_array($transaction->meta ?? null) ? $transaction->meta : [];
    $type = strtolower((string) ($meta['type'] ?? ''));
    $isDeposit = $type === \Incevio\Package\Wallet\Models\Transaction::TYPE_DEPOSIT
        || str_contains(strtolower((string) ($meta['description'] ?? '')), 'deposit')
        || str_contains(strtolower((string) ($meta['description'] ?? '')), 'carreg');
    $platformFee = (float) ($meta['platform_fee'] ?? $meta['fee'] ?? 0);
    $chargeTotal = (float) ($meta['charge_amount'] ?? 0);
    if ($chargeTotal <= 0 && $platformFee > 0) {
        $chargeTotal = round((float) $transaction->amount + $platformFee, 2);
    }
    $grossSale = (float) ($meta['gross_sale_amount'] ?? 0);
    $marketplaceCommission = (float) ($meta['marketplace_commission'] ?? $meta['sales_commission'] ?? 0);
    if (! $isDeposit && $marketplaceCommission <= 0 && $platformFee > 0) {
        $marketplaceCommission = $platformFee;
    }
    $netVendor = (float) ($meta['net_vendor_amount'] ?? $transaction->amount);
@endphp
@if ($isDeposit && $platformFee > 0)
  <tr>
    <td>@lang('invoice.wallet_credit')</td>
    <td>{{ get_formated_currency($transaction->amount, 2) }}</td>
  </tr>
  <tr>
    <td>@lang('invoice.transaction_fee')</td>
    <td>{{ get_formated_currency($platformFee, 2) }}</td>
  </tr>
  <tr>
    <td style="background: #e6f2ff"><strong>@lang('invoice.total_paid_mobile')</strong></td>
    <td style="background: #e6f2ff"><strong>{{ get_formated_currency($chargeTotal > 0 ? $chargeTotal : $transaction->amount + $platformFee, 2) }}</strong></td>
  </tr>
@elseif (! $isDeposit && $grossSale > 0 && $marketplaceCommission > 0)
  <tr>
    <td>@lang('invoice.gross_sale')</td>
    <td>{{ get_formated_currency($grossSale, 2) }}</td>
  </tr>
  <tr>
    <td>@lang('invoice.marketplace_commission')</td>
    <td>{{ get_formated_currency($marketplaceCommission, 2) }}</td>
  </tr>
  <tr>
    <td style="background: #e6f2ff"><strong>@lang('invoice.vendor_net')</strong></td>
    <td style="background: #e6f2ff"><strong>{{ get_formated_currency($netVendor, 2) }}</strong></td>
  </tr>
@elseif ($platformFee > 0)
  <tr>
    <td>@lang('invoice.platform_fee')</td>
    <td>{{ get_formated_currency($platformFee, 2) }}</td>
  </tr>
@endif
