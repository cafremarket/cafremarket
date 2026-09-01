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
            <div class="card-box text-center h-100 nearby-store-card">
              <a href="{{ route('show.store', $shop->slug) }}" class="text-reset">
                <div class="thumb-lg d-flex thumbnail rounded-circle justify-content-center align-items-center mx-auto p-2">
                  <img class="lazy w-100" src="{{ get_storage_file_url(optional($shop->logoImage)->path, 'tiny_thumb') }}" data-src="{{ get_storage_file_url(optional($shop->logoImage)->path, 'full') }}" alt="{{ $shop->name }}">
                </div>
                <h4 class="mb-1 mt-2">{!! $shop->getQualifiedName(10) !!}</h4>
              </a>

              @if (isset($distances[$shop->id]))
                <p class="text-muted mb-2">
                  <i class="fal fa-map-marker-alt"></i>
                  {{ number_format($distances[$shop->id], 1) }} km
                </p>
              @endif

              @if (isset($shop->active_inventories_count) && (int) $shop->active_inventories_count === 0)
                <p class="text-warning small mb-2">{{ trans('theme.no_products_listed_yet') }}</p>
              @endif

              @include('theme::layouts.ratings', ['ratings' => $shop->ratings, 'count' => $shop->ratings_count ?? 0])

              <a href="{{ route('show.store', $shop->slug) }}" class="btn btn-default btn-rounded mt-3 waves-effect w-md waves-light">
                {{ trans('theme.visit_shop_page') }}
              </a>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>
@elseif (session('buyer_latitude'))
  <section class="nearby-stores-section pb-5">
    <div class="container">
      <div class="alert alert-warning text-center py-4">
        <i class="fal fa-store-slash fa-2x mb-2 d-block"></i>
        <p class="mb-2">{{ trans('theme.no_stores_nearby') }}</p>
        <button type="button" class="btn btn-outline-primary btn-round" data-toggle="modal" data-target="#locationModal">
          {{ trans('theme.change_location') }}
        </button>
      </div>
    </div>
  </section>
@endif
