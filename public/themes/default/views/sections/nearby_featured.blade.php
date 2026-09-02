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
@endif
