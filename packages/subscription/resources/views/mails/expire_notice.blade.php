@component('mail::message')
  #{{ trans('packages.subscription.greeting', ['shop' => $receiver]) }}

  {{ trans('packages.subscription.expire.message') }}
  <br />
  {{ trans('packages.subscription.expire.expire_date', ['date' => date('d-m-Y', strtotime($expire_date))]) }}
  <br />

  @component('mail::button', ['url' => $url, 'color' => 'blue'])
    {{ trans('packages.subscription.button_text') }}
  @endcomponent

  {{ trans('packages.subscription.thanks') }},<br>
  {{ get_platform_title() }}
@endcomponent
