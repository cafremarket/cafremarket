@php
  $frameSize = $frameSize ?? 'md';
  $frameClass = trim('sf-shop-logo-frame sf-shop-logo-frame--' . $frameSize . ' ' . ($class ?? ''));
@endphp

<div class="{{ $frameClass }}">
  @include('theme::partials._shop_logo', [
    'shop' => $shop,
    'thumbSize' => $thumbSize ?? 'tiny_thumb',
    'fullSize' => $fullSize ?? 'full',
  ])
</div>
