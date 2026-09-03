@php
  $sfCssPath = theme_assets_path('css/storefront-modern.css');
  $sfCssVer = is_file($sfCssPath) ? filemtime($sfCssPath) : time();
@endphp
<link rel="stylesheet" href="{{ theme_asset_url('css/storefront-modern.css') }}?v={{ $sfCssVer }}">
