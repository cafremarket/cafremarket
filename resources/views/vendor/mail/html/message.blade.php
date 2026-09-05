@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
<img src="{{ get_logo_url('system', 'full') }}" width="180" class="brand-logo" alt="{{ get_platform_title() }}" title="{{ get_platform_title() }}">
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
**{{ get_platform_title() }}**

[Visit website]({{ config('app.url') }})

© {{ date('Y') }} {{ get_platform_title() }}. All rights reserved.
@endcomponent
@endslot
@endcomponent
