@if (isset($nearbyFeaturedItems) && $nearbyFeaturedItems->count())
  <section class="nearby-featured-section py-4">
    <div class="container">
      <div class="home-section-heading">
        <h2>{{ trans('theme.featured_nearby') }} <i class="fal fa-star text-warning"></i></h2>
        <p>{{ trans('theme.featured_nearby_subtitle') }}</p>
        <div class="accent-line"></div>
      </div>

      <div class="nearby-featured-slider owl-carousel">
        @include('theme::partials._product_horizontal', ['products' => $nearbyFeaturedItems])
      </div>
    </div>
  </section>
@elseif (!session('buyer_latitude'))
  <section class="nearby-featured-section py-4">
    <div class="container">
      <div class="home-section-heading mb-3">
        <h2>{{ trans('theme.featured_nearby') }}</h2>
        <p>{{ trans('theme.set_location_to_see_products') }}</p>
        <div class="accent-line"></div>
      </div>
      <div class="sf-skeleton-grid" aria-hidden="true">
        @for ($i = 0; $i < 5; $i++)
          <div class="sf-skeleton-card">
            <div class="sf-skeleton sf-img"></div>
            <div class="sf-skeleton sf-line"></div>
            <div class="sf-skeleton sf-line short"></div>
          </div>
        @endfor
      </div>
    </div>
  </section>
@endif
