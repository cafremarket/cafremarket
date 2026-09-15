@component('mail::message')
{{ trans('notifications.order_wire_transfer_rejected.greeting', ['customer' => $order->customer->getName()]) }}

{{ trans('notifications.order_wire_transfer_rejected.message', ['order' => $order->order_number]) }}

@if ($reason)
> {{ $reason }}
@endif
<br/>

@component('mail::button', ['url' => $url, 'color' => 'red'])
{{ trans('notifications.order_wire_transfer_rejected.button_text') }}
@endcomponent

@include('admin.mail.order._order_detail_panel', ['order_detail' => $order])

{{ trans('messages.thanks') }},<br>
{{ $order->shop->name  . ', ' . get_platform_title() }}
@endcomponent
