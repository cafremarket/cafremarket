@if (isset($featuredShops) && $featuredShops->count())
  <section id="featured-stores" class="nearby-stores-section sf-home-stores-section pb-5">
    <div class="container">
      <div class="sf-home-stores-head">
        <div class="sf-home-stores-head__main">
          <h2 class="sf-home-stores-head__title">
            {{ trans('theme.featured_stores') }}
            <i class="fal fa-store" aria-hidden="true"></i>
          </h2>
        </div>
      </div>

      <div class="row sf-stores-grid">
        @foreach ($featuredShops as $row)
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-4 col-6 mb-3">
            @include('theme::partials._shop_card', [
              'shop' => $row['shop'],
              'distance' => $row['distance_km'] ?? null,
            ])
          </div>
        @endforeach
      </div>
    </div>
  </section>
@endif
