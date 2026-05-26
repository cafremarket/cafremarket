<head>
  <meta charset="utf-8" />
</head>
<style>
  /** Must be added for multi-language support **/
  @font-face {
    font-family: 'NotoMono-Regular';
    src: url('{{ storage_path('fonts/NotoMono/NotoMono-Regular.ttf') }}') format('truetype');
  }

  /*For Chinese Font support*/
  @font-face {
    font-family: 'NotoSansSC';
    src: url('{{ storage_path('fonts/NotoMono/NotoSansSC-Regular.ttf') }}') format('truetype');
  }

  @font-face {
    font-family: 'SourceSansPro'
      src: url('{{ storage_path('fonts/SourceSansPro/SourceSansPro-Regular.ttf') }}') format('truetype');
  }

  body {
    font-family: 'DejaVu Sans', 'NotoSansSC', 'SourceSansPro';
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  td,
  th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 8px;
  }
</style>

@php
  $order = $data->order ?? $data;
@endphp
<img src="data:image/png;base64,{{ DNS1D::getBarcodePNG((string) $order->id, 'C39+') }}" alt="barcode" />
<div style="width:100%; text-align:center; background-color:lightgrey;">
  <h5>{{ trans('app.shipping_label') }}</h5>
</div>
<div style="width:100%">
  <div style="float:left;">
    <h5>{{ trans('app.order') }}: {{ $order->order_number }}</h5>
  </div>
  <h5 style="float:right;">{{ trans('app.order_date') }}: {{ $order->created_at->format('d/m/y') }}</h5>
  <div style="clear:both;"></div>
</div>
<div style="width:100%">
  <div style="float:left;">
    <u>{{ trans('app.from') }}</u><br />
    @if (isset($order->shop->name) && !empty($order->shop->name))
      <b>{{ $order->shop->name }}</b><br />
    @endif
    @if (isset($order->shop->address->address_line_1) && !empty($order->shop->address->address_line_1))
      {{ $order->shop->address->address_line_1 }}<br />
    @endif
    @if (isset($order->shop->address->address_line_2) && !empty($order->shop->address->address_line_2))
      {{ $order->shop->address->address_line_2 }}<br />
    @endif
    @php
      $shopCity = $order->shop->address->city ?? null;
      $shopCityLine = is_object($shopCity) ? (string) ($shopCity->name ?? '') : (string) $shopCity;
    @endphp
    @if ($shopCityLine !== '')
      {{ $shopCityLine }}<br />
    @endif
    @if (isset($order->shop->address->state->name) && !empty($order->shop->address->state->name))
      {{ $order->shop->address->state->name }}<br />
    @endif
    @if (isset($order->shop->address->country->name) && !empty($order->shop->address->country->name))
      {{ $order->shop->address->country->name }}<br />
    @endif
  </div>
  <div style="float:right;">
    <u>{{ trans('app.customer') }}</u><br />
    {{ $order->customer->name }}<br />
    {{ $order->customer->email }}<br />
    {{ $order->customer->phone }}
  </div>
  <div style="clear:both;"></div>
</div>


<h4 style="width:100%; text-align:center; background-color:lightgrey;">{{ trans('app.product') }}</h4>
<table>
  <thead>
    <tr>
      <th>{{ trans('app.product') }}</th>
      <th>{{ trans('app.quantity') }}</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($order->inventories as $item)
      <tr>
        <td>{{ $item->title }}</td>
        <td>{{ $item->pivot->quantity }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
@if ((float) $order->shipping_weight > 0)
  <p style="margin-top: 12px;">
    <strong>{{ trans('app.shipping_weight') }}:</strong>
    {{ number_format((float) $order->shipping_weight, 2, '.', '') . config('system_settings.weight_unit') }}
  </p>
@endif
