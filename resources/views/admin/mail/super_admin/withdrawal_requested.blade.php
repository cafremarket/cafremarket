@component('mail::message')
{{ trans('notifications.withdrawal_requested.greeting') }}

{!! trans('notifications.withdrawal_requested.message', ['shop_name' => e($shop_name), 'amount' => e($amount)]) !!}

@if ($payout_method)
**{{ trans('packages.wallet.payout_method') }}:** {{ $payout_method }}<br/>
@endif
@if ($instruction)
**{{ trans('packages.wallet.payout_saved_instruction') }}:** {{ $instruction }}
@endif

@component('mail::button', ['url' => $url, 'color' => 'green'])
{{ trans('notifications.withdrawal_requested.button_text') }}
@endcomponent

{{ trans('messages.thanks') }},<br>
{{ get_platform_title() }}
@endcomponent
