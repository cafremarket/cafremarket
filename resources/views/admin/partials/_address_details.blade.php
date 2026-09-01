@php
  $address = $address ?? null;
  $showMapLink = $showMapLink ?? true;
@endphp

@if ($address)
  <div class="admin-address-details">
    <div class="admin-address-details__grid">
      @if (filled($address->address_title))
        <div class="admin-address-details__item">
          <span class="admin-address-details__label">{{ trans('app.full_name') }}</span>
          <span class="admin-address-details__value">{{ $address->address_title }}</span>
        </div>
      @endif

      @if (filled($address->address_line_1))
        <div class="admin-address-details__item">
          <span class="admin-address-details__label">{{ trans('app.address_line_1') }}</span>
          <span class="admin-address-details__value">{{ $address->address_line_1 }}</span>
        </div>
      @endif

      @if (filled($address->address_line_2))
        <div class="admin-address-details__item">
          <span class="admin-address-details__label">{{ trans('app.address_line_2') }}</span>
          <span class="admin-address-details__value">{{ $address->address_line_2 }}</span>
        </div>
      @endif

      @if (filled($address->landmark))
        <div class="admin-address-details__item">
          <span class="admin-address-details__label">{{ trans('theme.placeholder.landmark') }}</span>
          <span class="admin-address-details__value">{{ $address->landmark }}</span>
        </div>
      @endif

      @if (filled($address->city))
        <div class="admin-address-details__item">
          <span class="admin-address-details__label">{{ trans('app.form.city') }}</span>
          <span class="admin-address-details__value">{{ $address->city }}</span>
        </div>
      @endif

      @if ($address->state_id && optional($address->state)->name)
        <div class="admin-address-details__item">
          <span class="admin-address-details__label">{{ trans('app.state') }}</span>
          <span class="admin-address-details__value">{{ $address->state->name }}</span>
        </div>
      @endif

      @if (filled($address->zip_code))
        <div class="admin-address-details__item">
          <span class="admin-address-details__label">{{ trans('app.zip_code') }}</span>
          <span class="admin-address-details__value">{{ $address->zip_code }}</span>
        </div>
      @endif

      @if ($address->country_id && optional($address->country)->name)
        <div class="admin-address-details__item">
          <span class="admin-address-details__label">{{ trans('app.country') }}</span>
          <span class="admin-address-details__value">{{ $address->country->name }}</span>
        </div>
      @endif

      @if (filled($address->phone))
        <div class="admin-address-details__item">
          <span class="admin-address-details__label">{{ trans('app.form.phone') }}</span>
          <span class="admin-address-details__value">{{ $address->phone }}</span>
        </div>
      @endif

      @if ($address->latitude && $address->longitude)
        <div class="admin-address-details__item admin-address-details__item--full">
          <span class="admin-address-details__label">{{ trans('app.map_coordinates') }}</span>
          <span class="admin-address-details__value">
            {{ number_format((float) $address->latitude, 6) }}, {{ number_format((float) $address->longitude, 6) }}
            @if ($showMapLink)
              <a href="https://www.openstreetmap.org/?mlat={{ $address->latitude }}&amp;mlon={{ $address->longitude }}#map=16/{{ $address->latitude }}/{{ $address->longitude }}"
                class="admin-address-details__map-link"
                target="_blank"
                rel="noopener noreferrer">
                <i class="fa fa-external-link"></i> {{ trans('app.view_on_map') }}
              </a>
            @endif
          </span>
        </div>
      @endif
    </div>
  </div>
@else
  <p class="text-muted admin-address-details__empty">{{ trans('app.not_available') }}</p>
@endif
