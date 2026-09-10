<head>
  <meta charset="utf-8" />
</head>
<style>
  @font-face {
    font-family: 'NotoMono-Regular';
    src: url('{{ storage_path('fonts/NotoMono/NotoMono-Regular.ttf') }}') format('truetype');
  }
  @font-face {
    font-family: 'NotoSansSC';
    src: url('{{ storage_path('fonts/NotoMono/NotoSansSC-Regular.ttf') }}') format('truetype');
  }
  @font-face {
    font-family: 'SourceSansPro';
    src: url('{{ storage_path('fonts/SourceSansPro/SourceSansPro-Regular.ttf') }}') format('truetype');
  }
  body {
    font-family: 'DejaVu Sans', 'NotoSansSC', 'SourceSansPro';
    font-size: 12px;
    color: #222;
  }
  table {
    width: 100%;
    border-collapse: collapse;
  }
  td, th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 8px;
  }
  .section {
    width: 100%;
    text-align: center;
    background-color: #dcdcdc;
    padding: 6px;
    margin: 12px 0 8px;
    font-weight: bold;
  }
  .r { text-align: right; }
  .c { text-align: center; }
  .totals td { border: none; padding: 4px 0; }
  .totals .grand td {
    border-top: 1px solid #222;
    padding-top: 8px;
    font-weight: bold;
  }
  .muted { color: #666; font-size: 11px; }
</style>

@php
  $order = $data->order ?? $data;
  $subtotal = 0;
  foreach ($order->inventories as $item) {
      $subtotal += (float) $item->pivot->unit_price * (int) $item->pivot->quantity;
  }
  $shippingCost = (float) ($order->shipping ?? 0);
  $handlingCost = (float) ($order->handling ?? 0);
@endphp

<img src="data:image/png;base64,{{ DNS1D::getBarcodePNG((string) $order->id, 'C39+') }}" alt="barcode" />

<div class="section">{{ trans('app.shipping_label') }}</div>

<table style="border:none; margin-bottom:10px;">
  <tr>
    <td style="border:none; width:50%;">
      <strong>{{ trans('app.order') }}:</strong> {{ $order->order_number }}
    </td>
    <td style="border:none; width:50%;" class="r">
      <strong>{{ trans('app.order_date') }}:</strong> {{ $order->created_at->format('d/m/Y') }}
    </td>
  </tr>
</table>

<table style="border:none; margin-bottom:10px;">
  <tr>
    <td style="border:none; width:50%; vertical-align:top;">
      <u>{{ trans('app.from') }}</u><br />
      @if (!empty($order->shop->name))
        <b>{{ $order->shop->name }}</b><br />
      @endif
      @if (!empty(optional($order->shop->address)->address_line_1))
        {{ $order->shop->address->address_line_1 }}<br />
      @endif
      @if (!empty(optional($order->shop->address)->address_line_2))
        {{ $order->shop->address->address_line_2 }}<br />
      @endif
      @php
        $shopCity = optional($order->shop->address)->city ?? null;
        $shopCityLine = is_object($shopCity) ? (string) ($shopCity->name ?? '') : (string) $shopCity;
      @endphp
      @if ($shopCityLine !== '')
        {{ $shopCityLine }}<br />
      @endif
      @if (!empty(optional(optional($order->shop->address)->state)->name))
        {{ $order->shop->address->state->name }}<br />
      @endif
      @if (!empty(optional(optional($order->shop->address)->country)->name))
        {{ $order->shop->address->country->name }}
      @endif
    </td>
    <td style="border:none; width:50%; vertical-align:top;">
      <u>{{ trans('app.ship_to') }}</u><br />
      <b>{{ optional($order->customer)->getName() ?: optional($order->customer)->name }}</b><br />
      @if ($order->shipping_address)
        {!! nl2br(e(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $order->shipping_address)))) !!}<br />
      @endif
      @if ($order->customer_phone_number ?: optional($order->customer)->phone)
        {{ $order->customer_phone_number ?: optional($order->customer)->phone }}
      @endif
    </td>
  </tr>
</table>

<div class="section">{{ trans('app.product') }} / {{ trans('app.pricing') }}</div>

<table>
  <thead>
    <tr>
      <th>{{ trans('app.product') }}</th>
      <th class="c">{{ trans('app.quantity') }}</th>
      <th class="c">{{ trans('app.dimensions') }}</th>
      <th class="r">{{ trans('app.price') }}</th>
      <th class="r">{{ trans('app.total') }}</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($order->inventories as $item)
      @php
        $lineTotal = (float) $item->pivot->unit_price * (int) $item->pivot->quantity;
        $unit = $item->distance_unit ?: 'cm';
        $hasDims = ((float) $item->length > 0) || ((float) $item->width > 0) || ((float) $item->height > 0);
        $fmtDim = static function ($value) {
            $formatted = number_format((float) $value, 2, '.', '');
            $formatted = rtrim(rtrim($formatted, '0'), '.');

            return $formatted === '' ? '0' : $formatted;
        };
        $dimsLabel = $hasDims
            ? $fmtDim($item->length).' × '.$fmtDim($item->width).' × '.$fmtDim($item->height).' '.$unit
            : '—';
      @endphp
      <tr>
        <td>{{ $item->pivot->item_description ?? $item->title }}</td>
        <td class="c">{{ $item->pivot->quantity }}</td>
        <td class="c">{{ $dimsLabel }}</td>
        <td class="r">{{ get_formated_currency($item->pivot->unit_price, 2, $order->currency_id) }}</td>
        <td class="r">{{ get_formated_currency($lineTotal, 2, $order->currency_id) }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<table class="totals" style="width:45%; margin-left:auto; margin-top:12px;">
  <tr>
    <td>{{ trans('app.subtotal') }}</td>
    <td class="r">{{ get_formated_currency($subtotal, 2, $order->currency_id) }}</td>
  </tr>
  @if ((float) $order->discount > 0)
    <tr>
      <td>{{ trans('app.discount') }}</td>
      <td class="r">- {{ get_formated_currency($order->discount, 2, $order->currency_id) }}</td>
    </tr>
  @endif
  @if ((float) $order->taxes > 0)
    <tr>
      <td>{{ trans('app.taxes') }}</td>
      <td class="r">{{ get_formated_currency($order->taxes, 2, $order->currency_id) }}</td>
    </tr>
  @endif
  @unless ($order->is_digital)
    <tr>
      <td>{{ trans('app.shipping') }}</td>
      <td class="r">{{ get_formated_currency($shippingCost, 2, $order->currency_id) }}</td>
    </tr>
    @if ($handlingCost > 0)
      <tr>
        <td>{{ trans('app.handling') }}</td>
        <td class="r">{{ get_formated_currency($handlingCost, 2, $order->currency_id) }}</td>
      </tr>
    @endif
    @if ((float) $order->packaging > 0)
      <tr>
        <td>{{ trans('app.packaging') }}</td>
        <td class="r">{{ get_formated_currency($order->packaging, 2, $order->currency_id) }}</td>
      </tr>
    @endif
  @endunless
  <tr class="grand">
    <td>{{ trans('app.grand_total') }}</td>
    <td class="r">{{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}</td>
  </tr>
</table>

@if ((float) $order->shipping_weight > 0)
  <p class="muted" style="margin-top:12px;">
    <strong>{{ trans('app.shipping_weight') }}:</strong>
    {{ number_format((float) $order->shipping_weight, 2, '.', '') . (config('system_settings.weight_unit') ?? 'gm') }}
  </p>
@endif
