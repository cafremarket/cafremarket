<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>@lang('invoice.invoice')</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 11px;
      color: #222;
      line-height: 1.45;
      margin: 0;
      padding: 24px 28px 30px;
    }
    table { width: 100%; border-collapse: collapse; }
    .muted { color: #666; font-size: 10px; }
    .section-title {
      font-size: 10px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: #555;
      margin: 0 0 6px;
      padding-bottom: 4px;
      border-bottom: 1px solid #ddd;
    }
    .items th {
      font-size: 10px;
      text-transform: uppercase;
      color: #555;
      text-align: left;
      padding: 8px 6px;
      border-top: 1px solid #222;
      border-bottom: 1px solid #222;
      background: #f5f5f5;
    }
    .items td {
      padding: 8px 6px;
      border-bottom: 1px solid #e8e8e8;
      vertical-align: middle;
    }
    .items .c { text-align: center; }
    .items .r { text-align: right; }
    .totals td { padding: 4px 0; vertical-align: middle; }
    .totals .r { text-align: right; }
    .totals .grand td {
      padding-top: 8px;
      border-top: 1px solid #222;
      font-weight: bold;
      font-size: 12px;
    }
    .footer {
      margin-top: 24px;
      padding-top: 10px;
      border-top: 1px solid #ddd;
      font-size: 9px;
      color: #888;
      text-align: center;
    }
    address { font-style: normal; margin: 0; }
  </style>
</head>
<body>
@php
  $order = $data->order ?? $data;
  $shop = $order->shop;
  $system = \App\Models\System::orderBy('id', 'asc')->first();
  $companyName = ($system->legal_name ?: null) ?: get_platform_title();
  $companyEmail = $system->support_email ?: $system->email;
  $companyPhone = $system->support_phone ?: optional(optional($system)->primaryAddress)->phone;
  $companyAddress = optional($system)->primaryAddress;
  $logoRel = optional($system?->logoImage)->path ?? optional($system?->iconImage)->path;
  $platformLogoPath = pdf_platform_logo_path();
  $platformLogoSrc = pdf_dompdf_storage_image_src($logoRel);

  $shopAddress = $shop ? ($shop->storeAddress() ?? optional($shop)->address) : null;
  $shopCity = is_object($shopAddress?->city ?? null)
      ? (string) ($shopAddress->city->name ?? '')
      : (string) ($shopAddress->city ?? '');

  $cCity = is_object($companyAddress->city ?? null)
      ? (string) ($companyAddress->city->name ?? '')
      : (string) ($companyAddress->city ?? '');

  $qrPayload = url('/order/' . $order->id);
  try {
      if (\Illuminate\Support\Facades\Route::has('order.detail')) {
          $qrPayload = route('order.detail', $order);
      }
  } catch (\Throwable $e) {
  }
  $qrSrc = pdf_invoice_qr_image_src($qrPayload, $platformLogoPath, 140);

  $payment_instructions = null;
  $payment_method = null;
  if (optional($order->paymentMethod)->type == \App\Models\PaymentMethod::TYPE_MANUAL) {
      if (vendor_get_paid_directly()) {
          $payment_method = optional(optional($shop)->config)->manualPaymentMethods
              ->where('id', $order->payment_method_id)
              ->first();
          $payment_instructions = optional($payment_method)->pivot->payment_instructions;
      } else {
          $payment_instructions = get_from_option_table('wallet_payment_instructions_' . optional($order->paymentMethod)->code);
      }
  }

  $subtotal = 0;
  foreach ($order->inventories as $item) {
      $subtotal += $item->pivot->unit_price * $item->pivot->quantity;
  }
@endphp

  {{-- Header: logo + company (same line) | invoice + QR --}}
  <table style="margin-bottom: 18px;">
    <tr>
      <td style="width: 58%; vertical-align: middle;">
        <table>
          <tr>
            <td style="width: 78px; vertical-align: middle; padding-right: 12px;">
              <img src="{{ $platformLogoSrc }}" width="72" height="56" style="object-fit: contain;" alt="">
            </td>
            <td style="vertical-align: middle;">
              <div style="font-size: 15px; font-weight: bold;">{{ $companyName }}</div>
              @if ($companyAddress)
                <div class="muted">
                  @if ($companyAddress->address_line_1){{ $companyAddress->address_line_1 }}@endif
                  @if ($companyAddress->address_line_2), {{ $companyAddress->address_line_2 }}@endif
                  @if ($cCity || optional($companyAddress->state)->name)
                    · {{ trim($cCity . (optional($companyAddress->state)->name ? ', ' . $companyAddress->state->name : '')) }}
                  @endif
                  @if (optional($companyAddress->country)->name)
                    · {{ $companyAddress->country->name }}
                  @endif
                </div>
              @endif
              @if ($companyEmail || $companyPhone)
                <div class="muted">
                  @if ($companyEmail){{ $companyEmail }}@endif
                  @if ($companyEmail && $companyPhone) · @endif
                  @if ($companyPhone){{ $companyPhone }}@endif
                </div>
              @endif
            </td>
          </tr>
        </table>
      </td>
      <td style="width: 42%; vertical-align: middle;">
        <table>
          <tr>
            <td style="vertical-align: middle; text-align: right; padding-right: 12px;">
              <div style="font-size: 18px; font-weight: bold; margin-bottom: 6px;">@lang('invoice.invoice')</div>
              <div class="muted">@lang('invoice.number')</div>
              <div style="font-weight: bold; margin-bottom: 4px;">{{ $order->order_number }}</div>
              <div class="muted">@lang('invoice.date')</div>
              <div>{{ $order->created_at->format('d M Y, h:i A') }}</div>
            </td>
            <td style="width: 108px; vertical-align: middle; text-align: right;">
              <img src="{{ $qrSrc }}" width="100" height="100" alt="QR">
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <div style="border-top: 2px solid #222; margin: 0 0 14px;"></div>

  <table style="margin-bottom: 16px;">
    <tr>
      <td style="width: 50%; padding-right: 12px; vertical-align: top;">
        <div class="section-title">@lang('invoice.seller_info')</div>
        <strong>{{ optional($shop)->name }}</strong><br>
        @if (optional($shop)->email)<span class="muted">{{ $shop->email }}</span><br>@endif
        @if ($shopAddress)
          <div class="muted">
            @if ($shopAddress->address_line_1){{ $shopAddress->address_line_1 }}<br>@endif
            @if ($shopAddress->address_line_2){{ $shopAddress->address_line_2 }}<br>@endif
            @if ($shopCity || optional($shopAddress->state)->name)
              {{ trim($shopCity . (optional($shopAddress->state)->name ? ', ' . $shopAddress->state->name : '')) }}<br>
            @endif
            @if (optional($shopAddress->country)->name){{ $shopAddress->country->name }}<br>@endif
            @if ($shopAddress->phone){{ $shopAddress->phone }}@endif
          </div>
        @endif
      </td>
      <td style="width: 50%; padding-left: 12px; vertical-align: top;">
        <div class="section-title">@lang('invoice.to')</div>
        <strong>{{ optional($order->customer)->name ?: ($order->email ?? '—') }}</strong><br>
        @if (optional($order->customer)->email || $order->email)
          <span class="muted">{{ optional($order->customer)->email ?: $order->email }}</span><br>
        @endif
        @if (optional($order->customer)->phone || $order->customer_phone_number)
          <span class="muted">{{ optional($order->customer)->phone ?: $order->customer_phone_number }}</span><br>
        @endif
        @unless ($order->is_digital)
          <div class="muted" style="margin-top: 8px; font-weight: bold; text-transform: uppercase; font-size: 9px;">@lang('invoice.delivery_address')</div>
          <div>{!! address_str_to_html($order->shipping_address) !!}</div>
        @endunless
      </td>
    </tr>
  </table>

  <table style="margin-bottom: 12px;">
    <tr>
      <td style="width: 50%; vertical-align: middle;">
        <span class="muted">@lang('invoice.payment_method'):</span>
        <strong> {{ optional($order->paymentMethod)->name ?: '—' }}</strong>
      </td>
      <td style="width: 50%; vertical-align: middle; text-align: right;">
        <span class="muted">@lang('invoice.payment_status'):</span>
        <strong> {{ $order->paymentStatusName(true) }}</strong>
      </td>
    </tr>
  </table>

  <table class="items" style="margin-bottom: 14px;">
    <thead>
      <tr>
        <th style="width: 8%;" class="c">#</th>
        <th>@lang('app.description')</th>
        <th style="width: 12%;" class="c">@lang('app.quantity')</th>
        <th style="width: 18%;" class="r">@lang('app.price')</th>
        <th style="width: 18%;" class="r">@lang('app.total')</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($order->inventories as $item)
        <tr>
          <td class="c">{{ $loop->iteration }}</td>
          <td>{{ $item->title }}</td>
          <td class="c">{{ $item->pivot->quantity }}</td>
          <td class="r">{{ get_formated_currency($item->pivot->unit_price, 2, $order->currency_id) }}</td>
          <td class="r">{{ get_formated_currency($item->pivot->unit_price * $item->pivot->quantity, 2, $order->currency_id) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <table>
    <tr>
      <td style="width: 52%; padding-right: 16px; vertical-align: top;">
        @if ($payment_instructions)
          <div class="section-title">@lang('invoice.payment_instruction')</div>
          <div class="muted">{!! $payment_instructions !!}</div>
        @elseif (optional($order->paymentMethod)->instructions)
          <div class="section-title">@lang('invoice.payment_instruction')</div>
          <div class="muted">{!! $order->paymentMethod->instructions !!}</div>
        @endif
        @if (isset($payment_method) && optional($payment_method)->pivot->additional_details)
          <div class="muted" style="margin-top: 8px;">
            <strong>@lang('invoice.additional_info')</strong><br>
            {{ $payment_method->pivot->additional_details }}
          </div>
        @endif
        @if ($order->buyer_note)
          <div class="muted" style="margin-top: 8px;">
            <strong>@lang('invoice.buyer_note')</strong><br>
            {{ $order->buyer_note }}
          </div>
        @endif
      </td>
      <td style="width: 48%; vertical-align: top;">
        <table class="totals">
          <tr>
            <td>@lang('app.total')</td>
            <td class="r">{{ get_formated_currency($subtotal, 2, $order->currency_id) }}</td>
          </tr>
          @if ((float) $order->discount > 0)
            <tr>
              <td>@lang('invoice.discount')</td>
              <td class="r">- {{ get_formated_currency($order->discount, 2, $order->currency_id) }}</td>
            </tr>
          @endif
          <tr>
            <td>@lang('invoice.taxes')</td>
            <td class="r">{{ get_formated_currency($order->taxes, 2, $order->currency_id) }}</td>
          </tr>
          @unless ($order->is_digital)
            <tr>
              <td>@lang('invoice.shipping')</td>
              <td class="r">{{ get_formated_currency($order->get_shipping_cost(), 2, $order->currency_id) }}</td>
            </tr>
            @if ((float) $order->packaging > 0)
              <tr>
                <td>@lang('invoice.packaging')</td>
                <td class="r">{{ get_formated_currency($order->packaging, 2, $order->currency_id) }}</td>
              </tr>
            @endif
            @if ((float) $order->handling > 0)
              <tr>
                <td>@lang('invoice.handling')</td>
                <td class="r">{{ get_formated_currency($order->handling, 2, $order->currency_id) }}</td>
              </tr>
            @endif
          @endunless
          <tr class="grand">
            <td>@lang('invoice.grand_total')</td>
            <td class="r">{{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}</td>
          </tr>
          @include('pdf_templates.partials.order_invoice_fees', ['order' => $order, 'invoiceTableColumns' => 2])
        </table>
      </td>
    </tr>
  </table>

  <div class="footer">
    {{ get_platform_title() }} · {{ url('/') }} · @lang('invoice.footer_note')
  </div>
</body>
</html>
