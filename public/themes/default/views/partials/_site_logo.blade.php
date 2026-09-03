@php
  $logoSize = $logoSize ?? 'logo';
  $logoHref = $href ?? url('/');
  $logoClass = trim('sf-site-logo brand-logo ' . ($class ?? ''));
  $textClass = trim('sf-site-brand-text ' . ($textClass ?? ''));
  $wrapLink = $wrapLink ?? true;
  $hasLogo = system_has_custom_logo();
  $brandLabel = get_platform_brand_label();
@endphp

@if ($hasLogo)
  @if ($wrapLink)
    <a href="{{ $logoHref }}" class="sf-site-logo-link" aria-label="{{ $brandLabel }}">
      <img
        src="{{ get_logo_url('system', $logoSize) }}"
        class="{{ $logoClass }}"
        alt="{{ $brandLabel }}"
        title="{{ $brandLabel }}"
        @if (! empty($height)) height="{{ $height }}" @endif
      >
    </a>
  @else
    <img
      src="{{ get_logo_url('system', $logoSize) }}"
      class="{{ $logoClass }}"
      alt="{{ $brandLabel }}"
      title="{{ $brandLabel }}"
      @if (! empty($height)) height="{{ $height }}" @endif
    >
  @endif
@else
  @if ($wrapLink)
    <a href="{{ $logoHref }}" class="sf-site-logo-link sf-site-brand-link" aria-label="{{ $brandLabel }}">
      <span class="{{ $textClass }}">{{ $brandLabel }}</span>
    </a>
  @else
    <span class="{{ $textClass }}">{{ $brandLabel }}</span>
  @endif
@endif
