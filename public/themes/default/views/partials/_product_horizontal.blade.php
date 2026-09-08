@php
  $sf_card_distances = app(\App\Services\Hyperlocal\HyperlocalCatalogService::class)->shopDistances();
@endphp

@foreach ($products as $item)
  <div class="items-slider">
    @include('theme::partials._product_card', [
      'item' => $item,
      'distance' => $sf_card_distances->get($item->shop_id),
    ])
  </div>
@endforeach
