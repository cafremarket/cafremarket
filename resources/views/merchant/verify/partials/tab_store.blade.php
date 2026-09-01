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
            {{ $storeAddress->address_title ? $storeAddress->address_title.', ' : '' }}
            {{ $storeAddress->address_line_1 }}{{ $storeAddress->city ? ', '.$storeAddress->city : '' }}
          </p>
        @else
          <div class="mp-alert mp-alert--warning"><i class="fa fa-exclamation-triangle"></i> {{ trans('app.store_location_required') }}</div>
        @endif

        @if ($canSubmit)
          {!! Form::open(['route' => 'merchant.verify.location', 'id' => 'store-location-form', 'class' => 'mp-address-wizard-form']) !!}
            @include('partials.address_wizard', [
              'wizardId' => 'merchant-store-wizard',
              'address' => $storeAddress,
              'countries' => $countries ?? [],
              'states' => $states ?? [],
              'iconPrefix' => 'fa',
              'submitLabel' => trans('app.save_store_location'),
              'defaultAddressTitle' => $shop->name,
              'defaultPhone' => $shopPhone ?? optional($config)->support_phone ?? Auth::user()->phone,
              'deferInit' => true,
            ])
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
