@if (isset($nearbyShopsPaginator) && $nearbyShopsPaginator->total() > 0)
  <section id="nearby-stores" class="nearby-stores-section sf-home-stores-section pb-5">
    <div class="container">
      <div class="sf-home-stores-head">
        <div class="sf-home-stores-head__main">
          <p class="sf-home-stores-head__eyebrow">{{ trans('theme.stores_near_you') }}</p>
          <h2 class="sf-home-stores-head__title">
            {{ trans('theme.stores') }}
            <i class="fal fa-store" aria-hidden="true"></i>
          </h2>
          @if (buyer_delivery_address_label())
            <p class="sf-home-stores-head__location">
              <i class="fal fa-map-marker-alt" aria-hidden="true"></i>
              {{ Str::limit(buyer_delivery_address_label(), 72) }}
            </p>
          @endif
          <p class="sf-home-stores-head__meta">
            {{ trans('theme.showing_stores_count', ['count' => $nearbyShopsPaginator->total()]) }}
          </p>
        </div>
      </div>

      <div class="row sf-stores-grid">
        @foreach ($nearbyShopsPaginator as $row)
          <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            @include('theme::partials._shop_card', [
              'shop' => $row['shop'],
              'distance' => $row['distance_km'] ?? null,
            ])
          </div>
        @endforeach
      </div>

      @if ($nearbyShopsPaginator->hasPages())
        <div class="sf-stores-pagination">
          {{ $nearbyShopsPaginator->links('theme::layouts.pagination') }}
        </div>
      @endif
    </div>
  </section>
@else
  <section id="nearby-stores" class="nearby-stores-section sf-home-stores-section pb-5">
    <div class="container">
      <div class="sf-home-stores-head">
        <div class="sf-home-stores-head__main">
          <p class="sf-home-stores-head__eyebrow">{{ trans('theme.stores_near_you') }}</p>
          <h2 class="sf-home-stores-head__title">
            {{ trans('theme.stores') }}
            <i class="fal fa-store" aria-hidden="true"></i>
          </h2>
          @if (buyer_delivery_address_label())
            <p class="sf-home-stores-head__location">
              <i class="fal fa-map-marker-alt" aria-hidden="true"></i>
              {{ Str::limit(buyer_delivery_address_label(), 72) }}
            </p>
          @endif
        </div>
      </div>

      @if (buyer_has_location())
        @include('theme::partials._no_stores_message', [
          'title' => trans('theme.no_store_found'),
          'message' => trans('theme.no_stores_nearby'),
          'showLocationButton' => true,
          'locationButtonText' => trans('theme.change_location'),
        ])
      @else
        @include('theme::partials._no_stores_message', [
          'title' => trans('theme.no_store_found'),
          'message' => trans('theme.set_location_to_see_products'),
          'showLocationButton' => true,
          'locationButtonText' => trans('theme.set_delivery_location'),
        ])
      @endif
    </div>
  </section>
@endif
