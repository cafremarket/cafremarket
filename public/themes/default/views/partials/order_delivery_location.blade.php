@php
  $isPickup = $order->pickup();
  $warehouse = $isPickup ? $order->warehouse : null;
  $pickupAddress = $warehouse ? $warehouse->pickupAddress() : null;

  $deliveryLat = $order->customer_latitude ? (float) $order->customer_latitude : null;
  $deliveryLng = $order->customer_longitude ? (float) $order->customer_longitude : null;
  $hasDeliveryPin = $deliveryLat && $deliveryLng;

  // For pickup orders the "destination" pin is the warehouse, not a delivery address.
  $destLat = $isPickup
      ? ($pickupAddress && $pickupAddress->latitude ? (float) $pickupAddress->latitude : null)
      : $deliveryLat;
  $destLng = $isPickup
      ? ($pickupAddress && $pickupAddress->longitude ? (float) $pickupAddress->longitude : null)
      : $deliveryLng;
  $hasDestPin = $destLat && $destLng;

  $storeAddress = optional($order->shop)->storeAddress();
  $storeLat = $storeAddress && $storeAddress->latitude ? (float) $storeAddress->latitude : null;
  $storeLng = $storeAddress && $storeAddress->longitude ? (float) $storeAddress->longitude : null;
  $hasStorePin = $storeLat && $storeLng;

  $distanceKm = null;
  if (! $isPickup && $hasDeliveryPin && $hasStorePin) {
      $distanceKm = app(\App\Services\Geo\DistanceService::class)
          ->distanceKm($storeLat, $storeLng, $deliveryLat, $deliveryLng);
  } elseif ($isPickup && $hasDeliveryPin && $hasDestPin) {
      $distanceKm = app(\App\Services\Geo\DistanceService::class)
          ->distanceKm($destLat, $destLng, $deliveryLat, $deliveryLng);
  }

  $mapQuery = $hasDestPin
      ? $destLat.','.$destLng
      : ($isPickup ? null : address_str_to_geocode_str($order->shipping_address));
  $mapsUrl = $hasDestPin
      ? 'https://www.google.com/maps?q='.urlencode($destLat.','.$destLng)
      : ($mapQuery ? 'https://www.google.com/maps?q='.urlencode(str_replace('+', ' ', (string) $mapQuery)) : null);
  $directionsUrl = $hasDestPin
      ? 'https://www.google.com/maps/dir/?api=1&destination='.urlencode($destLat.','.$destLng)
      : ($mapQuery ? 'https://www.google.com/maps/dir/?api=1&destination='.urlencode(str_replace('+', ' ', (string) $mapQuery)) : null);
  $compact = ! empty($compact);
@endphp

@unless ($order->is_digital)
  <div class="sf-order-location {{ $compact ? 'sf-order-location--compact' : '' }}">
    <div class="sf-order-location__head">
      <div>
        <h3 class="sf-order-location__title">
          <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
          @lang($isPickup ? 'theme.pickup_from' : 'theme.delivery_location')
        </h3>
        <p class="sf-order-location__lead">@lang($isPickup ? 'app.pickup_otp_help' : 'theme.order_will_deliver_here')</p>
      </div>
      @if ($distanceKm !== null)
        <span class="sf-order-location__badge">
          {{ format_distance_km($distanceKm) }} @lang($isPickup ? 'theme.from_you' : 'theme.from_store')
        </span>
      @endif
    </div>

    <div class="sf-order-location__grid">
      @if ($mapQuery)
        <div class="sf-order-location__map-wrap">
          <iframe
            class="sf-order-location__map"
            title="@lang($isPickup ? 'theme.pickup_from' : 'theme.delivery_location')"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            src="https://maps.google.com/maps?q={{ urlencode($mapQuery) }}&z=15&output=embed">
          </iframe>
        </div>
      @endif

      <div class="sf-order-location__details">
        @if ($isPickup)
          <div class="sf-order-location__card">
            <span class="sf-order-location__label">@lang('theme.pickup_from')</span>
            <div class="sf-order-location__store">{{ optional($warehouse)->name }}</div>
            @if ($pickupAddress)
              <div class="sf-order-location__address">
                {!! $pickupAddress->toHtml('<br/>', false) !!}
              </div>
            @endif
            @if (optional($warehouse)->pickup_instruction)
              <p class="sf-order-location__coords text-muted">{{ $warehouse->pickup_instruction }}</p>
            @endif
            @if (optional($warehouse)->opening_time && optional($warehouse)->close_time)
              <p class="sf-order-location__coords text-muted">
                {{ $warehouse->opening_time }} - {{ $warehouse->close_time }}
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
            </div>
          @endif
        @else
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
        @endif

        @if ($mapsUrl)
          <div class="sf-order-location__actions">
            <a class="btn btn-primary btn-sm" href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer">
              <i class="fas fa-map"></i> @lang('theme.open_in_maps')
            </a>
            <a class="btn btn-default btn-sm" href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer">
              <i class="fas fa-route"></i> @lang('theme.get_directions')
            </a>
          </div>
        @endif
      </div>
    </div>
  </div>
@endunless
