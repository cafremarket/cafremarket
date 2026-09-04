@php
  $deliveryLat = $order->customer_latitude ? (float) $order->customer_latitude : null;
  $deliveryLng = $order->customer_longitude ? (float) $order->customer_longitude : null;
  $hasDeliveryPin = $deliveryLat && $deliveryLng;
  $storeAddress = optional($order->shop)->storeAddress();
  $storeLat = $storeAddress && $storeAddress->latitude ? (float) $storeAddress->latitude : null;
  $storeLng = $storeAddress && $storeAddress->longitude ? (float) $storeAddress->longitude : null;
  $hasStorePin = $storeLat && $storeLng;
  $distanceKm = null;
  if ($hasDeliveryPin && $hasStorePin) {
      $distanceKm = app(\App\Services\Geo\DistanceService::class)
          ->distanceKm($storeLat, $storeLng, $deliveryLat, $deliveryLng);
  }
  $mapQuery = $hasDeliveryPin
      ? $deliveryLat.','.$deliveryLng
      : address_str_to_geocode_str($order->shipping_address);
  $mapsUrl = $hasDeliveryPin
      ? 'https://www.google.com/maps?q='.urlencode($deliveryLat.','.$deliveryLng)
      : 'https://www.google.com/maps?q='.urlencode(str_replace('+', ' ', (string) $mapQuery));
  $directionsUrl = $hasDeliveryPin
      ? 'https://www.google.com/maps/dir/?api=1&destination='.urlencode($deliveryLat.','.$deliveryLng)
      : 'https://www.google.com/maps/dir/?api=1&destination='.urlencode(str_replace('+', ' ', (string) $mapQuery));
  $compact = ! empty($compact);
@endphp

@unless ($order->is_digital)
  <div class="sf-order-location {{ $compact ? 'sf-order-location--compact' : '' }}">
    <div class="sf-order-location__head">
      <div>
        <h3 class="sf-order-location__title">
          <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
          @lang('theme.delivery_location')
        </h3>
        <p class="sf-order-location__lead">@lang('theme.order_will_deliver_here')</p>
      </div>
      @if ($distanceKm !== null)
        <span class="sf-order-location__badge">
          {{ format_distance_km($distanceKm) }} @lang('theme.from_store')
        </span>
      @endif
    </div>

    <div class="sf-order-location__grid">
      <div class="sf-order-location__map-wrap">
        <iframe
          class="sf-order-location__map"
          title="@lang('theme.delivery_location')"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          src="https://maps.google.com/maps?q={{ urlencode($mapQuery) }}&z=15&output=embed">
        </iframe>
      </div>

      <div class="sf-order-location__details">
        <div class="sf-order-location__card">
          <span class="sf-order-location__label">@lang('theme.deliver_to')</span>
          <div class="sf-order-location__address">
            {!! address_str_to_html($order->shipping_address) !!}
          </div>
          @if ($hasDeliveryPin)
            <p class="sf-order-location__coords text-muted">
              {{ number_format($deliveryLat, 5) }}, {{ number_format($deliveryLng, 5) }}
            </p>
          @endif
        </div>

        @if ($order->shop)
          <div class="sf-order-location__card">
            <span class="sf-order-location__label">@lang('theme.sold_by')</span>
            <div class="sf-order-location__store">
              @if ($order->shop->slug)
                <a href="{{ route('show.store', $order->shop->slug) }}">{{ $order->shop->name }}</a>
              @else
                {{ $order->shop->name }}
              @endif
            </div>
            @if ($storeAddress)
              <div class="sf-order-location__address sf-order-location__address--muted">
                {!! $storeAddress->toHtml('<br/>', false) !!}
              </div>
            @endif
          </div>
        @endif

        <div class="sf-order-location__actions">
          <a class="btn btn-primary btn-sm" href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer">
            <i class="fas fa-map"></i> @lang('theme.open_in_maps')
          </a>
          <a class="btn btn-default btn-sm" href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer">
            <i class="fas fa-route"></i> @lang('theme.get_directions')
          </a>
        </div>
      </div>
    </div>
  </div>
@endunless
