@php
  $wizardId = $wizardId ?? 'addr-wizard';
  $address = $address ?? null;
  $iconPrefix = $iconPrefix ?? 'fal';
  $initialLat = old('latitude', optional($address)->latitude ?: session('buyer_latitude'));
  $initialLng = old('longitude', optional($address)->longitude ?: session('buyer_longitude'));
  $startAtStep = (old('latitude') && old('longitude')) || (isset($address) && $address->latitude && $address->longitude) ? 2 : 1;
  $initialLocationText = optional($address)->toString(true) ?: session('buyer_address_text');
  $googleMapsKey = google_maps_api_key();
@endphp

<div class="address-wizard" id="{{ $wizardId }}" data-wizard-id="{{ $wizardId }}">
  {{-- Step indicators --}}
  <div class="address-wizard-steps mb-3">
    <span class="address-wizard-step {{ $startAtStep === 1 ? 'is-active' : '' }}" data-step="1">
      <span class="step-num">1</span> {{ trans('theme.address_step_location') }}
    </span>
    <span class="address-wizard-step {{ $startAtStep === 2 ? 'is-active' : '' }}" data-step="2">
      <span class="step-num">2</span> {{ trans('theme.address_step_details') }}
    </span>
  </div>

  {{-- Step 1: Map location --}}
  <div class="address-wizard-panel {{ $startAtStep === 2 ? 'd-none' : '' }}" data-panel="1">
    <p class="text-muted text-center mb-3">{{ trans('theme.address_step_location_help') }}</p>

    <div class="form-group location-search-wrap">
      <label>{{ trans('theme.search_address') }}</label>
      <input type="text" class="form-control form-control-lg addr-wizard-search" placeholder="{{ trans('theme.search_address_placeholder') }}" autocomplete="off" value="">
      <ul class="location-autocomplete-list addr-wizard-autocomplete d-none"></ul>
    </div>

    <div class="location-map-wrap">
      <div class="location-map-canvas addr-wizard-map"></div>
      <button type="button" class="location-map-current-btn addr-wizard-map-gps" title="{{ trans('theme.map_current_location') }}" aria-label="{{ trans('theme.map_current_location') }}">
        <i class="{{ $iconPrefix }} fa-crosshairs"></i>
      </button>
      <p class="text-muted small mt-2 mb-0">{{ trans('theme.drag_map_to_adjust_pin') }}</p>
    </div>

    <div class="text-center my-3">
      <span class="text-muted">{{ trans('theme.or') }}</span>
    </div>

    <button type="button" class="btn btn-outline-primary btn-block btn-lg btn-round addr-wizard-use-gps">
      <i class="{{ $iconPrefix }} fa-crosshairs"></i> {{ trans('theme.use_current_location') }}
    </button>

    <div class="alert alert-light mt-3 addr-wizard-preview d-none">
      <i class="{{ $iconPrefix }} fa-check-circle text-success"></i>
      <span class="addr-wizard-preview-text"></span>
    </div>

    <button type="button" class="btn btn-primary btn-block btn-lg btn-round mt-3 addr-wizard-next" disabled>
      {{ trans('theme.continue_to_address_details') }}
    </button>
  </div>

  {{-- Step 2: Complete address --}}
  <div class="address-wizard-panel {{ $startAtStep === 1 ? 'd-none' : '' }}" data-panel="2">
    <p class="text-muted text-center mb-3">{{ trans('theme.address_step_details_help') }}</p>

    <div class="alert alert-light addr-wizard-selected-location mb-3">
      <i class="{{ $iconPrefix }} fa-map-marker text-primary"></i>
      <span class="addr-wizard-selected-text">{{ $initialLocationText }}</span>
      <button type="button" class="btn btn-link btn-sm float-right p-0 addr-wizard-back">{{ trans('theme.change') }}</button>
    </div>

    @if (isset($address_types))
      <div class="form-group">
        {!! Form::select('address_type', $address_types, optional($address)->address_type, ['class' => 'form-control flat', 'placeholder' => trans('theme.placeholder.address_type') . '*', 'required']) !!}
        <div class="help-block with-errors"></div>
      </div>
    @endif

    <div class="form-group">
      {!! Form::text('address_title', old('address_title', optional($address)->address_title ?: ($defaultAddressTitle ?? '')), ['class' => 'form-control flat', 'placeholder' => trans('theme.placeholder.full_name') . '*', 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>

    <div class="form-group">
      {!! Form::text('address_line_1', optional($address)->address_line_1, ['class' => 'form-control flat', 'placeholder' => trans('theme.placeholder.address_line_1') . '*', 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>

    <div class="form-group">
      {!! Form::text('address_line_2', optional($address)->address_line_2, ['class' => 'form-control flat', 'placeholder' => trans('theme.placeholder.address_line_2')]) !!}
      <div class="help-block with-errors"></div>
    </div>

    <div class="form-group">
      {!! Form::text('landmark', optional($address)->landmark, ['class' => 'form-control flat', 'placeholder' => trans('theme.placeholder.landmark')]) !!}
      <div class="help-block with-errors"></div>
    </div>

    <div class="row">
      <div class="col-md-8 pr-md-1">
        <div class="form-group">
          {!! Form::select('country_id', $countries ?? [], optional($address)->country_id ?: config('system_settings.address_default_country'), ['class' => 'form-control flat addr-wizard-country', 'placeholder' => trans('theme.country') . '*', 'required']) !!}
          <div class="help-block with-errors"></div>
        </div>
      </div>
      <div class="col-md-4 pl-md-1">
        <div class="form-group">
          {!! Form::text('zip_code', optional($address)->zip_code, ['class' => 'form-control flat', 'placeholder' => trans('theme.placeholder.zip_code')]) !!}
          <div class="help-block with-errors"></div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 pr-md-1">
        <div class="form-group">
          {!! Form::text('city', optional($address)->city, ['class' => 'form-control flat', 'placeholder' => trans('theme.placeholder.city') . '*', 'required']) !!}
          <div class="help-block with-errors"></div>
        </div>
      </div>
      <div class="col-md-6 pl-md-1">
        <div class="form-group">
          {!! Form::select('state_id', $states ?? [], optional($address)->state_id ?: config('system_settings.address_default_state'), ['class' => 'form-control flat addr-wizard-state', 'placeholder' => trans('theme.placeholder.state')]) !!}
          <div class="help-block with-errors"></div>
        </div>
      </div>
    </div>

    <div class="form-group">
      {!! Form::text('phone', old('phone', optional($address)->phone ?: ($defaultPhone ?? '')), ['class' => 'form-control flat', 'placeholder' => trans('theme.placeholder.phone_number') . '*', 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>

    <input type="hidden" name="latitude" class="addr-wizard-lat" value="{{ $initialLat }}">
    <input type="hidden" name="longitude" class="addr-wizard-lng" value="{{ $initialLng }}">

    <button type="submit" class="btn btn-primary btn-block btn-lg btn-round mt-3">
      {{ $submitLabel ?? trans('theme.button.save_address') }}
    </button>
  </div>
</div>

<style>
  .address-wizard-steps {
    display: flex;
    gap: 8px;
    justify-content: center;
  }
  .address-wizard-step {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    background: #f3f4f6;
    color: #6b7280;
    font-size: 13px;
    font-weight: 600;
  }
  .address-wizard-step.is-active {
    background: #fff7ed;
    color: #c2410c;
  }
  .address-wizard-step .step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #e5e7eb;
    font-size: 12px;
  }
  .address-wizard-step.is-active .step-num {
    background: #f97316;
    color: #fff;
  }
  .address-wizard .location-search-wrap {
    position: relative;
    z-index: 20;
  }
  .address-wizard .location-autocomplete-list {
    position: absolute;
    top: calc(100% - 4px);
    left: 0;
    right: 0;
    max-height: 200px;
    overflow-y: auto;
    margin: 0;
    padding: 6px 0;
    list-style: none;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    z-index: 100060;
  }
  .address-wizard .location-autocomplete-list li {
    padding: 10px 14px;
    cursor: pointer;
    font-size: 14px;
    border-bottom: 1px solid #f3f4f6;
  }
  .address-wizard .location-autocomplete-list li:hover {
    background: #fff7ed;
    color: #c2410c;
  }
  .address-wizard .location-map-wrap {
    position: relative;
    margin-top: 12px;
  }
  .address-wizard .location-map-canvas {
    width: 100%;
    height: 260px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    background: #f3f4f6;
  }
  .address-wizard .location-map-current-btn {
    position: absolute;
    right: 12px;
    bottom: 36px;
    z-index: 500;
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 50%;
    background: #fff;
    color: #f97316;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.18);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    cursor: pointer;
  }
</style>

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
