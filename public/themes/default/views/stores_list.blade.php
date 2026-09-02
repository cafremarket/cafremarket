@extends('theme::layouts.main')

@section('content')
  <div class="sf-stores-page">
    @include('theme::partials._stores_page_hero', [
      'title' => ($isNearby ?? false) ? trans('theme.stores_near_you') : trans('theme.all_stores'),
      'subtitle' => ($isNearby ?? false) ? trans('theme.showing_stores_near_you') : trans('theme.browse_all_stores'),
    ])

    <section class="nearby-stores-section pb-5">
      <div class="container">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
          <div class="home-section-heading mb-0">
            <h2>
              {{ ($isNearby ?? false) ? trans('theme.stores_near_you') : trans('theme.stores') }}
              <i class="fal fa-store"></i>
            </h2>
            @if ($isNearby ?? false)
              @if (buyer_delivery_address_label())
                <p>
                  <i class="fal fa-map-marker-alt"></i>
                  {{ Str::limit(buyer_delivery_address_label(), 60) }}
                </p>
              @endif
            @elseif (method_exists($shops, 'total') && $shops->total() > 0)
              <p>{{ trans('theme.showing_stores_count', ['count' => $shops->total()]) }}</p>
            @endif
            <div class="accent-line"></div>
          </div>

          @if ($isNearby ?? false)
            <a href="{{ route('shops', ['scope' => 'all']) }}" class="btn btn-outline-primary btn-round btn-sm mt-2 sf-stores-view-all">
              {{ trans('theme.view_all_stores') }}
            </a>
          @endif
        </div>

        @if ($shops->isEmpty())
          @include('theme::partials._no_stores_message', [
            'title' => trans('theme.no_store_found'),
            'message' => trans('theme.no_stores_nearby'),
            'showLocationButton' => true,
            'locationButtonText' => ($isNearby ?? false) ? trans('theme.change_location') : trans('theme.set_delivery_location'),
          ])
        @else
          <div class="row sf-stores-grid">
            @foreach ($shops as $shop)
              <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                @include('theme::partials._shop_card', [
                  'shop' => $shop,
                  'distance' => isset($distances) ? ($distances[$shop->id] ?? null) : null,
                ])
              </div>
            @endforeach
          </div>

          @if (method_exists($shops, 'links'))
            <div class="sf-stores-pagination">
              {{ $shops->links('theme::layouts.pagination') }}
            </div>
          @endif
        @endif
      </div>
    </section>

    @if (!($isNearby ?? false))
      @include('theme::sections.recent_views')
    @endif
  </div>
@endsection
