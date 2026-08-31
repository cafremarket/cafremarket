@php
  $currentLocale = config('active_locales')->firstWhere('code', app()->getLocale());
  $currentLabel = $currentLocale->language ?? strtoupper(app()->getLocale());
@endphp

<div class="mp-lang">
  <button type="button" class="mp-lang__toggle" aria-haspopup="true" aria-expanded="false">
    <i class="fa fa-globe"></i>
    <span>{{ $currentLabel }}</span>
    <i class="fa fa-chevron-down mp-lang__chevron"></i>
  </button>
  <ul class="mp-lang__menu">
    @foreach (config('active_locales') as $lang)
      <li>
        <a href="{{ route('locale.change', $lang->code) }}" class="{{ app()->getLocale() === $lang->code ? 'is-active' : '' }}">
          {{ $lang->language }}
        </a>
      </li>
    @endforeach
  </ul>
</div>
