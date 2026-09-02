@if (isset($nearbyShops) && $nearbyShops->count())
  <section class="nearby-stores-section pb-5">
    <div class="container">
      <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div class="home-section-heading mb-0">
          <h2>{{ trans('theme.stores_near_you') }} <i class="fal fa-store"></i></h2>
          @if (session('buyer_address_text'))
            <p>
              <i class="fal fa-map-marker-alt"></i>
              {{ Str::limit(session('buyer_address_text'), 60) }}
            </p>
          @endif
          <div class="accent-line"></div>
        </div>
        <a href="{{ route('shops', ['lat' => session('buyer_latitude'), 'lng' => session('buyer_longitude')]) }}" class="btn btn-outline-primary btn-round btn-sm mt-2">
          {{ trans('theme.view_all_stores') }}
        </a>
      </div>

      <div class="row">
        @foreach ($nearbyShops as $shop)
          <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            @include('theme::partials._shop_card', [
              'shop' => $shop,
              'distance' => $distances[$shop->id] ?? null,
            ])
          </div>
        @endforeach
      </div>
    </div>
  </section>
@else
  <section class="nearby-stores-section pb-5">
    <div class="container">
      <div class="home-section-heading mb-3">
        <h2>{{ trans('theme.stores_near_you') }} <i class="fal fa-store"></i></h2>
        @if (session('buyer_address_text'))
          <p>
            <i class="fal fa-map-marker-alt"></i>
            {{ Str::limit(session('buyer_address_text'), 60) }}
          </p>
        @endif
        <div class="accent-line"></div>
      </div>

      @if (session('buyer_latitude') && session('buyer_longitude'))
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
