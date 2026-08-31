@if (isset($nearbyFeaturedItems) && $nearbyFeaturedItems->count())
  <section class="nearby-featured-section">
    <div class="container">
      <div class="sell-header mb-3">
        <div class="sell-header-title">
          <h2 class="mb-1">
            {{ trans('theme.featured_nearby') }}
            <i class="fal fa-star"></i>
          </h2>
          <p class="text-muted mb-0">{{ trans('theme.featured_nearby_subtitle') }}</p>
        </div>
        <div class="header-line"><span></span></div>
      </div>

      <div class="nearby-featured-slider owl-carousel">
        @include('theme::partials._product_horizontal', ['products' => $nearbyFeaturedItems])
      </div>
    </div>
  </section>
@elseif (!session('buyer_latitude'))
  <section class="nearby-featured-section">
    <div class="container">
      <div class="alert alert-info text-center py-4">
        <i class="fal fa-map-marker-alt fa-2x mb-2 d-block"></i>
        <p class="mb-2">{{ trans('theme.set_location_to_see_products') }}</p>
        <button type="button" class="btn btn-primary btn-round" data-toggle="modal" data-target="#locationModal">
          {{ trans('theme.set_delivery_location') }}
        </button>
      </div>
    </div>
  </section>
@endif
