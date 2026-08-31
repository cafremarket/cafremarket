<div class="mp-verify-tab-panel" id="mp-tab-store" role="tabpanel" hidden>
  <div class="mp-verify-subtabs">
    <div class="mp-panel mp-panel--flat">
      <div class="mp-panel__head">
        <div class="mp-panel__head-icon"><i class="fa fa-map-marker"></i></div>
        <div class="mp-panel__head-text">
          <h2>{{ trans('app.address_verification') }}</h2>
          <p>{{ trans('messages.verification_option_address_help') }}</p>
        </div>
        @if ($shop->address_verified)
          <span class="mp-verify-tab-badge mp-verify-tab-badge--success">{{ trans('app.verified') }}</span>
        @elseif ($hasLocation)
          <span class="mp-verify-tab-badge mp-verify-tab-badge--info">{{ trans('app.submitted') }}</span>
        @else
          <span class="mp-verify-tab-badge mp-verify-tab-badge--warning">{{ trans('app.action_required') }}</span>
        @endif
      </div>
      <div class="mp-panel__body">
        @if ($hasLocation)
          <div class="mp-alert mp-alert--success"><i class="fa fa-check-circle"></i> {{ trans('app.store_location_set') }}</div>
          <p class="mp-text-muted mp-text-muted--spaced">
            {{ $storeAddress->address_line_1 }}{{ $storeAddress->city ? ', '.$storeAddress->city : '' }}
          </p>
        @else
          <div class="mp-alert mp-alert--warning"><i class="fa fa-exclamation-triangle"></i> {{ trans('app.store_location_required') }}</div>
        @endif

        @if ($canSubmit)
          {!! Form::open(['route' => 'merchant.verify.location', 'id' => 'store-location-form']) !!}
            <div class="mp-form-grid">
              <div class="mp-form-group">
                {!! Form::label('address_line_1', trans('app.form.address_line_1')) !!}
                {!! Form::text('address_line_1', old('address_line_1', optional($storeAddress)->address_line_1), ['class' => 'mp-form-control', 'placeholder' => trans('app.placeholder.address_line_1')]) !!}
              </div>
              <div class="mp-form-group">
                {!! Form::label('city', trans('app.form.city')) !!}
                {!! Form::text('city', old('city', optional($storeAddress)->city), ['class' => 'mp-form-control', 'placeholder' => trans('app.placeholder.city')]) !!}
              </div>
            </div>

            @if (config('services.google.place_api_key'))
              <div class="mp-map-wrap">
                @include('partials.map_pin_picker', [
                  'latitude' => old('latitude', optional($storeAddress)->latitude),
                  'longitude' => old('longitude', optional($storeAddress)->longitude),
                  'skipMapsScript' => true,
                ])
              </div>
            @else
              <div class="mp-alert mp-alert--info">
                <i class="fa fa-info-circle"></i> {{ trans('messages.seller_onboarding_map_unavailable') }}
              </div>
              <button type="button" id="verify-use-current-location" class="map-current-location-btn mp-btn-spaced">
                <i class="fa fa-crosshairs"></i> {{ trans('theme.use_current_location') }}
              </button>
              <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', optional($storeAddress)->latitude) }}">
              <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', optional($storeAddress)->longitude) }}">
            @endif

            <button type="submit" class="mp-btn mp-btn--primary">
              <i class="fa fa-map-pin"></i> {{ trans('app.save_store_location') }}
            </button>
          {!! Form::close() !!}
        @endif
      </div>
    </div>

    <div class="mp-panel mp-panel--flat">
      <div class="mp-panel__head">
        <div class="mp-panel__head-icon"><i class="fa fa-file-text-o"></i></div>
        <div class="mp-panel__head-text">
          <h2>{{ trans('app.store_document_verification') }}</h2>
          <p>{{ trans('messages.verification_tab_store_documents_help') }}</p>
        </div>
        @if ($hasStoreDocs)
          <span class="mp-verify-tab-badge mp-verify-tab-badge--info">{{ trans('app.submitted') }}</span>
        @else
          <span class="mp-verify-tab-badge mp-verify-tab-badge--warning">{{ trans('app.action_required') }}</span>
        @endif
      </div>
      <div class="mp-panel__body">
        <div class="mp-alert mp-alert--info">
          <i class="fa fa-info-circle"></i> {!! trans('messages.verification_store_documents') !!}
        </div>

        @if ($canSubmit)
          @include('merchant.verify.partials.documents', [
            'config' => $config,
            'editable' => true,
            'attachments' => $storeAttachments,
            'documentType' => 'store',
          ])
        @else
          @include('merchant.verify.partials.documents', [
            'config' => $config,
            'editable' => false,
            'attachments' => $storeAttachments,
            'documentType' => 'store',
          ])
        @endif
      </div>
    </div>
  </div>
</div>
