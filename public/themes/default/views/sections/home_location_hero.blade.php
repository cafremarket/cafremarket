<section class="home-location-hero">
  <div class="container">
    <div class="home-location-hero__inner">
      <div class="home-location-hero__text">
        <h1>{{ trans('theme.stores_near_you') }}</h1>
        <p>{{ trans('theme.set_location_to_see_products') }}</p>
      </div>

      <button type="button" class="home-location-picker" data-toggle="modal" data-target="#locationModal" aria-label="{{ trans('theme.set_delivery_location') }}">
        <span class="home-location-picker__icon"><i class="fal fa-map-marker-alt"></i></span>
        <span class="home-location-picker__body">
          <span class="home-location-picker__label">{{ trans('theme.deliver_to') }}</span>
          @if (session('buyer_address_text'))
            <span class="home-location-picker__value">{{ Str::limit(session('buyer_address_text'), 42) }}</span>
          @else
            <span class="home-location-picker__value is-empty">{{ trans('theme.set_delivery_location') }}</span>
          @endif
        </span>
        <span class="home-location-picker__action">
          @if (session('buyer_address_text'))
            {{ trans('theme.change') }}
          @else
            {{ trans('theme.set_delivery_location') }}
          @endif
        </span>
      </button>
    </div>
  </div>
</section>
