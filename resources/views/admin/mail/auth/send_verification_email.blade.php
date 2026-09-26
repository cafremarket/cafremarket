@component('mail::message')
{{ trans('notifications.email_verification.greeting', ['user' => $user->getName()]) }}

{{ trans('notifications.email_verification.message') }}
<br/>

@if (! empty($code))
{{ trans('notifications.email_verification.code_intro') }}

@component('mail::panel')
<div style="font-size: 28px; font-weight: bold; letter-spacing: 8px; text-align: center;">{{ $code }}</div>
@endcomponent

{{ trans('notifications.email_verification.code_expiry', ['minutes' => $minutes]) }}
@endif

@component('mail::button', ['url' => $url, 'color' => 'blue'])
{{ trans('notifications.email_verification.button_text') }}
@endcomponent

{{ trans('notifications.email_verification.ignore') }}

{{ trans('messages.thanks') }},<br>
{{ get_platform_title() }}
@endcomponent
