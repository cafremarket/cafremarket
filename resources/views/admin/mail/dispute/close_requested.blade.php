@component('mail::message')
{{ trans('notifications.dispute_close_requested.greeting') }}

{{ trans('notifications.dispute_close_requested.message', ['order_id' => $dispute->order->order_number, 'ticket' => $dispute->ticketRef()]) }}
<br/>

@component('mail::button', ['url' => $url, 'color' => 'orange'])
{{ trans('notifications.dispute_close_requested.button_text') }}
@endcomponent

@include('admin.mail.dispute._dispute_detail_panel', ['dispute_detail' => $dispute])

{{ trans('messages.thanks') }},<br>
{{ get_platform_title() }}
@endcomponent
