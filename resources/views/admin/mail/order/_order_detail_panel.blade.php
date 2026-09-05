@component('mail::panel')
<table class="mail-meta" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="mail-meta__label">{{ trans('messages.shop_name') }}</td>
<td class="mail-meta__value">{{ $order_detail->shop->name }}</td>
</tr>
<tr>
<td class="mail-meta__label">{{ trans('messages.order_id') }}</td>
<td class="mail-meta__value">{{ $order_detail->order_number }}</td>
</tr>
<tr>
<td class="mail-meta__label">{{ trans('messages.payment_method') }}</td>
<td class="mail-meta__value">{{ $order_detail->paymentMethod->name }}</td>
</tr>
<tr>
<td class="mail-meta__label">{{ trans('messages.payment_status') }}</td>
<td class="mail-meta__value">{!! $order_detail->paymentStatusName(true) !!}</td>
</tr>
<tr>
<td class="mail-meta__label">{{ trans('messages.order_status') }}</td>
<td class="mail-meta__value"><strong>{!! $order_detail->orderStatus(true) !!}</strong></td>
</tr>
@if ($order_detail->carrier_id)
<tr>
<td class="mail-meta__label">{{ trans('messages.shipping_carrier') }}</td>
<td class="mail-meta__value">{{ $order_detail->carrier->name }}</td>
</tr>
@endif
@if ($order_detail->tracking_id)
@php
  $tracking_url = getTrackingUrl($order_detail->tracking_id, $order_detail->carrier_id);
@endphp
<tr>
<td class="mail-meta__label">{{ trans('messages.tracking_id') }}</td>
<td class="mail-meta__value"><a href="{{ $tracking_url }}" target="_blank" rel="noopener">{{ $order_detail->tracking_id }}</a></td>
</tr>
@endif
<tr>
<td class="mail-meta__label">{{ trans('messages.shipping_address') }}</td>
<td class="mail-meta__value">{!! $order_detail->shipping_address !!}</td>
</tr>
<tr>
<td class="mail-meta__label">{{ trans('messages.billing_address') }}</td>
<td class="mail-meta__value">{!! $order_detail->billing_address !!}</td>
</tr>
</table>
@endcomponent
