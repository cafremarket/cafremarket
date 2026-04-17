@component('mail::message')
  {{ trans('packages.wallet.greeting', ['receiver' => $receiver]) }}

  {{ trans('packages.wallet.created_amount', ['amount' => $amount]) }}

  @component('mail::button', ['url' => $url, 'color' => 'blue'])
    {{ trans('packages.wallet.see_now') }}
  @endcomponent

  {{ trans('packages.inspector.thanks') }},<br>
  {{ get_platform_title() }}
  <br />
@endcomponent
