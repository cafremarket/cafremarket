@php
  $sf_catalog = app(\App\Services\Hyperlocal\HyperlocalCatalogService::class);
  $sf_card_distances = $sf_catalog->shopDistances();
  $sf_card_out_of_range = $sf_catalog->outOfRangeShopIds();
@endphp

@foreach ($products as $item)
  <div class="items-slider">
    @include('theme::partials._product_card', [
      'item' => $item,
      'distance' => $sf_card_distances->get($item->shop_id),
      'outOfRange' => in_array($item->shop_id, $sf_card_out_of_range),
    ])
  </div>
@endforeach
