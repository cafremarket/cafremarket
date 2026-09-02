@php
  $thumbSize = $thumbSize ?? 'tiny_thumb';
  $fullSize = $fullSize ?? 'full';
  $hasCustomLogo = shop_has_custom_logo($shop);
  $defaultLogo = default_shop_logo_url($thumbSize);
@endphp

<img
  class="sf-shop-logo lazy{{ $hasCustomLogo ? '' : ' sf-shop-logo--default' }}"
  src="{{ $hasCustomLogo ? get_logo_url($shop, $thumbSize) : $defaultLogo }}"
  data-src="{{ $hasCustomLogo ? get_logo_url($shop, $fullSize) : $defaultLogo }}"
  alt="{{ $shop->name }}"
  width="72"
  height="72"
  onerror="this.onerror=null;this.src='{{ $defaultLogo }}';this.classList.add('sf-shop-logo--default');"
>
