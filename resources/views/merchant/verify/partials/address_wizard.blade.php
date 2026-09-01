@php
  $wizardId = $wizardId ?? 'merchant-store-wizard';
  $address = $address ?? null;
  $initialLat = old('latitude', optional($address)->latitude);
  $initialLng = old('longitude', optional($address)->longitude);
  $startAtStep = (old('latitude') && old('longitude')) || (isset($address) && $address->latitude && $address->longitude) ? 2 : 1;
  $initialLocationText = optional($address)->toString(true);
  $googleMapsKey = google_maps_api_key();
@endphp

<div class="mp-address-wizard address-wizard" id="{{ $wizardId }}" data-wizard-id="{{ $wizardId }}">
  <div class="mp-address-wizard__steps address-wizard-steps">
    <span class="mp-address-wizard__step address-wizard-step {{ $startAtStep === 1 ? 'is-active' : '' }}" data-step="1">
      <span class="mp-address-wizard__step-num step-num">1</span>
      <span class="mp-address-wizard__step-label">{{ trans('theme.address_step_location') }}</span>
    </span>
    <span class="mp-address-wizard__step address-wizard-step {{ $startAtStep === 2 ? 'is-active' : '' }}" data-step="2">
      <span class="mp-address-wizard__step-num step-num">2</span>
      <span class="mp-address-wizard__step-label">{{ trans('theme.address_step_details') }}</span>
    </span>
  </div>

  {{-- Step 1: Map location --}}
  <div class="mp-address-wizard__panel address-wizard-panel {{ $startAtStep === 2 ? 'is-hidden' : '' }}" data-panel="1">
    <p class="mp-address-wizard__intro">{{ trans('theme.address_step_location_help') }}</p>

    <div class="mp-form-group location-search-wrap">
      <label for="{{ $wizardId }}-search">{{ trans('theme.search_address') }}</label>
      <input type="text"
        id="{{ $wizardId }}-search"
        class="mp-form-control mp-form-control--lg addr-wizard-search"
        placeholder="{{ trans('theme.search_address_placeholder') }}"
        autocomplete="off"
        value="">
      <ul class="mp-address-wizard__autocomplete location-autocomplete-list addr-wizard-autocomplete is-hidden"></ul>
    </div>

    <div class="mp-address-wizard__map-wrap location-map-wrap">
      <div class="mp-address-wizard__map location-map-canvas addr-wizard-map"></div>
      <button type="button"
        class="mp-address-wizard__map-gps location-map-current-btn addr-wizard-map-gps"
        title="{{ trans('theme.map_current_location') }}"
        aria-label="{{ trans('theme.map_current_location') }}">
        <i class="fa fa-crosshairs"></i>
      </button>
      <p class="mp-address-wizard__map-hint">{{ trans('theme.drag_map_to_adjust_pin') }}</p>
    </div>

    <div class="mp-address-wizard__or">
      <span>{{ trans('theme.or') }}</span>
    </div>

    <button type="button" class="mp-btn mp-btn--outline mp-btn--block map-current-location-btn addr-wizard-use-gps">
      <i class="fa fa-crosshairs"></i> {{ trans('theme.use_current_location') }}
    </button>

    <div class="mp-address-wizard__preview addr-wizard-preview is-hidden">
      <i class="fa fa-check-circle"></i>
      <span class="addr-wizard-preview-text"></span>
    </div>

    <button type="button" class="mp-btn mp-btn--primary mp-btn--block addr-wizard-next" disabled>
      {{ trans('theme.continue_to_address_details') }}
    </button>
  </div>

  {{-- Step 2: Complete address --}}
  <div class="mp-address-wizard__panel address-wizard-panel {{ $startAtStep === 1 ? 'is-hidden' : '' }}" data-panel="2">
    <p class="mp-address-wizard__intro">{{ trans('theme.address_step_details_help') }}</p>

    <div class="mp-address-wizard__selected addr-wizard-selected-location">
      <div class="mp-address-wizard__selected-text">
        <i class="fa fa-map-marker"></i>
        <span class="addr-wizard-selected-text">{{ $initialLocationText }}</span>
      </div>
      <button type="button" class="mp-address-wizard__change addr-wizard-back">{{ trans('theme.change') }}</button>
    </div>

    <div class="mp-form-group">
      {!! Form::text('address_title', old('address_title', optional($address)->address_title ?: ($defaultAddressTitle ?? '')), ['class' => 'mp-form-control', 'placeholder' => trans('theme.placeholder.full_name') . '*', 'required']) !!}
    </div>

    <div class="mp-form-group">
      {!! Form::text('address_line_1', old('address_line_1', optional($address)->address_line_1), ['class' => 'mp-form-control', 'placeholder' => trans('theme.placeholder.address_line_1') . '*', 'required']) !!}
    </div>

    <div class="mp-form-group">
      {!! Form::text('address_line_2', old('address_line_2', optional($address)->address_line_2), ['class' => 'mp-form-control', 'placeholder' => trans('theme.placeholder.address_line_2')]) !!}
    </div>

    <div class="mp-form-group">
      {!! Form::text('landmark', old('landmark', optional($address)->landmark), ['class' => 'mp-form-control', 'placeholder' => trans('theme.placeholder.landmark')]) !!}
    </div>

    <div class="mp-form-grid">
      <div class="mp-form-group">
        {!! Form::select('country_id', $countries ?? [], old('country_id', optional($address)->country_id ?: config('system_settings.address_default_country')), ['class' => 'mp-form-control addr-wizard-country', 'placeholder' => trans('theme.country') . '*', 'required']) !!}
      </div>
      <div class="mp-form-group">
        {!! Form::text('zip_code', old('zip_code', optional($address)->zip_code), ['class' => 'mp-form-control', 'placeholder' => trans('theme.placeholder.zip_code')]) !!}
      </div>
    </div>

    <div class="mp-form-grid">
      <div class="mp-form-group">
        {!! Form::text('city', old('city', optional($address)->city), ['class' => 'mp-form-control', 'placeholder' => trans('theme.placeholder.city') . '*', 'required']) !!}
      </div>
      <div class="mp-form-group">
        {!! Form::select('state_id', $states ?? [], old('state_id', optional($address)->state_id ?: config('system_settings.address_default_state')), ['class' => 'mp-form-control addr-wizard-state', 'placeholder' => trans('theme.placeholder.state')]) !!}
      </div>
    </div>

    <div class="mp-form-group">
      {!! Form::text('phone', old('phone', optional($address)->phone ?: ($defaultPhone ?? '')), ['class' => 'mp-form-control', 'placeholder' => trans('theme.placeholder.phone_number') . '*', 'required']) !!}
    </div>

    <input type="hidden" name="latitude" class="addr-wizard-lat" value="{{ $initialLat }}">
    <input type="hidden" name="longitude" class="addr-wizard-lng" value="{{ $initialLng }}">

    <button type="submit" class="mp-btn mp-btn--primary mp-btn--block">
      <i class="fa fa-map-pin"></i> {{ $submitLabel ?? trans('app.save_store_location') }}
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
  'hiddenClass' => 'is-hidden',
])
