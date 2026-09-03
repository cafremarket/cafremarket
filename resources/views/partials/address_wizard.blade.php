@php
  $wizardId = $wizardId ?? 'addr-wizard';
  $address = $address ?? null;
  $iconPrefix = $iconPrefix ?? 'fa';
  $initialLat = old('latitude', optional($address)->latitude ?: session('buyer_latitude'));
  $initialLng = old('longitude', optional($address)->longitude ?: session('buyer_longitude'));
  $startAtStep = (old('latitude') && old('longitude')) || (isset($address) && $address->latitude && $address->longitude) ? 2 : 1;
  $initialLocationText = optional($address)->toString(true) ?: session('buyer_address_text');
  $googleMapsKey = google_maps_api_key();
@endphp

<div class="sf-address-wizard sf-form address-wizard" id="{{ $wizardId }}" data-wizard-id="{{ $wizardId }}">
  {{-- Step indicators --}}
  <div class="sf-address-wizard__steps address-wizard-steps">
    <div class="sf-address-wizard__step address-wizard-step {{ $startAtStep === 1 ? 'is-active' : '' }}" data-step="1">
      <span class="sf-address-wizard__step-num step-num">1</span>
      <span class="sf-address-wizard__step-label">{{ trans('theme.address_step_location') }}</span>
    </div>
    <div class="sf-address-wizard__step-line" aria-hidden="true"></div>
    <div class="sf-address-wizard__step address-wizard-step {{ $startAtStep === 2 ? 'is-active' : '' }}" data-step="2">
      <span class="sf-address-wizard__step-num step-num">2</span>
      <span class="sf-address-wizard__step-label">{{ trans('theme.address_step_details') }}</span>
    </div>
  </div>

  {{-- Step 1: Map location --}}
  <div class="address-wizard-panel sf-address-wizard__panel {{ $startAtStep === 2 ? 'd-none' : '' }}" data-panel="1">
    <p class="sf-address-wizard__intro">{{ trans('theme.address_step_location_help') }}</p>

    <div class="sf-form-group sf-address-wizard__search location-search-wrap">
      <label class="sf-form-label" for="{{ $wizardId }}-search">{{ trans('theme.search_address') }}</label>
      <div class="sf-input-icon-wrap">
        <i class="{{ $iconPrefix }} fa-search" aria-hidden="true"></i>
        <input
          type="text"
          id="{{ $wizardId }}-search"
          class="form-control sf-input addr-wizard-search"
          placeholder="{{ trans('theme.search_address_placeholder') }}"
          autocomplete="off"
          value=""
        >
      </div>
      <ul class="sf-address-wizard__autocomplete location-autocomplete-list addr-wizard-autocomplete d-none"></ul>
    </div>

    <div class="sf-address-wizard__map-card location-map-wrap">
      <div class="location-map-canvas addr-wizard-map"></div>
      <button type="button" class="location-map-current-btn addr-wizard-map-gps" title="{{ trans('theme.map_current_location') }}" aria-label="{{ trans('theme.map_current_location') }}">
        <i class="{{ $iconPrefix }} fa-crosshairs"></i>
      </button>
      <p class="sf-address-wizard__map-hint">{{ trans('theme.drag_map_to_adjust_pin') }}</p>
    </div>

    <div class="sf-address-wizard__divider">
      <span>{{ trans('theme.or') }}</span>
    </div>

    <button type="button" class="btn sf-btn-outline btn-block addr-wizard-use-gps">
      <i class="{{ $iconPrefix }} fa-crosshairs"></i>
      <span class="addr-wizard-gps-label">{{ trans('theme.use_current_location') }}</span>
    </button>

    <div class="sf-address-wizard__preview addr-wizard-preview d-none" role="status">
      <i class="{{ $iconPrefix }} fa-check-circle" aria-hidden="true"></i>
      <span class="addr-wizard-preview-text"></span>
    </div>

    <button type="button" class="btn sf-btn-primary btn-block sf-address-wizard__next addr-wizard-next" disabled>
      {{ trans('theme.continue_to_address_details') }}
      <i class="{{ $iconPrefix }} fa-arrow-right" aria-hidden="true"></i>
    </button>
  </div>

  {{-- Step 2: Complete address --}}
  <div class="address-wizard-panel sf-address-wizard__panel sf-address-wizard__panel--details {{ $startAtStep === 1 ? 'd-none' : '' }}" data-panel="2">
    <p class="sf-address-wizard__intro">{{ trans('theme.address_step_details_help') }}</p>

    <div class="sf-address-wizard__picked addr-wizard-selected-location">
      <div class="sf-address-wizard__picked-icon">
        <i class="{{ $iconPrefix }} fa-map-marker" aria-hidden="true"></i>
      </div>
      <div class="sf-address-wizard__picked-body">
        <span class="sf-address-wizard__picked-label">@lang('theme.set_delivery_location')</span>
        <span class="addr-wizard-selected-text">{{ $initialLocationText }}</span>
      </div>
      <button type="button" class="btn btn-link sf-address-wizard__change addr-wizard-back">{{ trans('theme.change') }}</button>
    </div>

    @if (isset($address_types))
      <div class="sf-form-group">
        <label class="sf-form-label" for="{{ $wizardId }}-address-type">{{ trans('theme.placeholder.address_type') }} *</label>
        {!! Form::select('address_type', $address_types, optional($address)->address_type, ['id' => $wizardId . '-address-type', 'class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.address_type'), 'required']) !!}
        <div class="help-block with-errors"></div>
      </div>
    @endif

    <div class="sf-form-group">
      <label class="sf-form-label" for="{{ $wizardId }}-address-title">{{ trans('theme.placeholder.full_name') }} *</label>
      {!! Form::text('address_title', old('address_title', optional($address)->address_title ?: ($defaultAddressTitle ?? '')), ['id' => $wizardId . '-address-title', 'class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.full_name'), 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>

    <div class="sf-form-group">
      <label class="sf-form-label" for="{{ $wizardId }}-address-line-1">{{ trans('theme.placeholder.address_line_1') }} *</label>
      {!! Form::text('address_line_1', optional($address)->address_line_1, ['id' => $wizardId . '-address-line-1', 'class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.address_line_1'), 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>

    <div class="sf-form-group">
      <label class="sf-form-label" for="{{ $wizardId }}-address-line-2">{{ trans('theme.placeholder.address_line_2') }}</label>
      {!! Form::text('address_line_2', optional($address)->address_line_2, ['id' => $wizardId . '-address-line-2', 'class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.address_line_2')]) !!}
      <div class="help-block with-errors"></div>
    </div>

    <div class="sf-form-group">
      <label class="sf-form-label" for="{{ $wizardId }}-landmark">{{ trans('theme.placeholder.landmark') }}</label>
      {!! Form::text('landmark', optional($address)->landmark, ['id' => $wizardId . '-landmark', 'class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.landmark')]) !!}
      <div class="help-block with-errors"></div>
    </div>

    <div class="row">
      <div class="col-md-8">
        <div class="sf-form-group">
          <label class="sf-form-label" for="{{ $wizardId }}-country">{{ trans('theme.country') }} *</label>
          {!! Form::select('country_id', $countries ?? [], optional($address)->country_id ?: config('system_settings.address_default_country'), ['id' => $wizardId . '-country', 'class' => 'form-control sf-input addr-wizard-country', 'placeholder' => trans('theme.country'), 'required']) !!}
          <div class="help-block with-errors"></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="sf-form-group">
          <label class="sf-form-label" for="{{ $wizardId }}-zip">{{ trans('theme.placeholder.zip_code') }}</label>
          {!! Form::text('zip_code', optional($address)->zip_code, ['id' => $wizardId . '-zip', 'class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.zip_code')]) !!}
          <div class="help-block with-errors"></div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="sf-form-group">
          <label class="sf-form-label" for="{{ $wizardId }}-city">{{ trans('theme.placeholder.city') }} *</label>
          {!! Form::text('city', optional($address)->city, ['id' => $wizardId . '-city', 'class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.city'), 'required']) !!}
          <div class="help-block with-errors"></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="sf-form-group">
          <label class="sf-form-label" for="{{ $wizardId }}-state">{{ trans('theme.placeholder.state') }}</label>
          {!! Form::select('state_id', $states ?? [], optional($address)->state_id ?: config('system_settings.address_default_state'), ['id' => $wizardId . '-state', 'class' => 'form-control sf-input addr-wizard-state', 'placeholder' => trans('theme.placeholder.state')]) !!}
          <div class="help-block with-errors"></div>
        </div>
      </div>
    </div>

    <div class="sf-form-group">
      <label class="sf-form-label" for="{{ $wizardId }}-phone">{{ trans('theme.placeholder.phone_number') }} *</label>
      {!! Form::text('phone', old('phone', optional($address)->phone ?: ($defaultPhone ?? '')), ['id' => $wizardId . '-phone', 'class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.phone_number'), 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>

    <input type="hidden" name="latitude" class="addr-wizard-lat" value="{{ $initialLat }}">
    <input type="hidden" name="longitude" class="addr-wizard-lng" value="{{ $initialLng }}">

    <button type="submit" class="btn sf-btn-primary btn-block sf-address-wizard__submit">
      <i class="{{ $iconPrefix }} fa-check" aria-hidden="true"></i>
      {{ $submitLabel ?? trans('theme.button.save_address') }}
    </button>
  </div>
</div>

@include('theme::scripts.address_wizard', [
  'wizardId' => $wizardId,
  'initialLat' => $initialLat ?: '-25.9655',
  'initialLng' => $initialLng ?: '32.5832',
  'hasExistingCoords' => $initialLat && $initialLng ? 'true' : 'false',
  'startAtStep' => $startAtStep,
  'initialLocationText' => $initialLocationText ?? '',
  'googleMapsKey' => $googleMapsKey,
  'deferInit' => ! empty($deferInit),
])
