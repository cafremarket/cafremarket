@component('mail::message')
{{ trans('notifications.dispute_closed.greeting') }}

{{ trans('notifications.dispute_closed.message', ['order_id' => $dispute->order->order_number, 'ticket' => $dispute->ticketRef()]) }}
<br/>

@component('mail::button', ['url' => $url, 'color' => 'green'])
{{ trans('notifications.dispute_closed.button_text') }}
@endcomponent

@include('admin.mail.dispute._dispute_detail_panel', ['dispute_detail' => $dispute])

{{ trans('messages.thanks') }},<br>
{{ get_platform_title() }}
@endcomponent
