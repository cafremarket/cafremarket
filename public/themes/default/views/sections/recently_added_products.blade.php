@if (isset($recentlyAddedItems) && $recentlyAddedItems->count())
  @php
    $sf_catalog = app(\App\Services\Hyperlocal\HyperlocalCatalogService::class);
    $sf_card_distances = $sf_catalog->shopDistances();
    $sf_card_out_of_range = $sf_catalog->outOfRangeShopIds();
  @endphp
  <section class="sf-recently-added-section pb-5">
    <div class="container md-100">
      <div class="sell-header mb-3">
        <div class="sell-header-title">
          <h2>
            {{ trans('theme.recently_added') }}
            <i class="fal fa-clock text-warning"></i>
          </h2>
        </div>
        <div class="header-line"><span></span></div>
      </div>

      <div class="sf-product-card-grid">
        @foreach ($recentlyAddedItems as $item)
          @include('theme::partials._product_card', [
            'item' => $item,
            'distance' => $sf_card_distances->get($item->shop_id),
            'outOfRange' => in_array($item->shop_id, $sf_card_out_of_range),
          ])
        @endforeach
      </div>
    </div>
  </section>
@endif
