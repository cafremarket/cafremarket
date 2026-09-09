@if ($products->isEmpty())
  <div class="sf-empty-stores text-center py-5 sf-search-empty">
    <div class="sf-empty-stores__icon">
      <i class="fal fa-search"></i>
    </div>
    <h3 class="sf-empty-stores__title">{{ trans('theme.no_product_found') }}</h3>
    <a href="{{ url('categories') }}" class="btn sf-btn-primary btn-round mt-2">
      {{ trans('theme.button.choose_from_categories') }}
    </a>
  </div>
@else
  @php
    $sf_search_catalog = app(\App\Services\Hyperlocal\HyperlocalCatalogService::class);
    $sf_search_distances = $sf_search_catalog->shopDistances();
    $sf_search_out_of_range = $sf_search_catalog->outOfRangeShopIds();
  @endphp

  <div class="row sf-search-grid">
    @foreach ($products as $item)
      <div class="col-6 col-sm-4 col-lg-3 mb-4">
        @include('theme::partials._product_card', [
          'item' => $item,
          'distance' => $sf_search_distances->get($item->shop_id),
          'outOfRange' => in_array($item->shop_id, $sf_search_out_of_range),
        ])
      </div>
    @endforeach
  </div>

  <div class="sf-search-pagination">
    {{ $products->appends(request()->input())->links('theme::layouts.pagination') }}
  </div>
@endif
