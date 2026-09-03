<div class="mp-verify-tab-panel" id="mp-tab-store" role="tabpanel" hidden>
  <div class="mp-verify-stack">
    <section class="mp-verify-card">
      <header class="mp-verify-card__head">
        <div>
          <h2>{{ trans('app.address_verification') }}</h2>
          <p>{{ trans('messages.verification_option_address_help') }}</p>
        </div>
        @if ($shop->address_verified)
          <span class="mp-chip mp-chip--success">{{ trans('app.verified') }}</span>
        @elseif ($hasLocation)
          <span class="mp-chip mp-chip--info">{{ trans('app.submitted') }}</span>
        @endif
      </header>

      @if (!empty($pendingAddressChangeRequest))
        <p class="mp-verify-card__note mp-verify-card__note--info">{{ trans('messages.address_change_request_pending') }}</p>
      @endif

      @if ($hasLocation && $storeAddress)
        <div class="mp-verify-address">
          <strong>{{ trans('app.store_location_set') }}</strong>
          <p>
            {{ $storeAddress->address_title ? $storeAddress->address_title.', ' : '' }}
            {{ $storeAddress->address_line_1 }}{{ $storeAddress->city ? ', '.$storeAddress->city : '' }}
          </p>
        </div>
      @else
        <p class="mp-verify-card__note mp-verify-card__note--warn">{{ trans('app.store_location_required') }}</p>
      @endif

      @if ($canSubmit)
        {!! Form::open(['route' => 'merchant.verify.location', 'id' => 'store-location-form', 'class' => 'mp-address-wizard-form']) !!}
          @include('merchant.verify.partials.address_wizard', [
            'wizardId' => 'merchant-store-wizard',
            'address' => $storeAddress,
            'countries' => $countries ?? [],
            'states' => $states ?? [],
            'defaultAddressTitle' => $shop->name,
            'defaultPhone' => $shopPhone ?? optional($config)->support_phone ?? Auth::user()->phone,
            'deferInit' => true,
          ])
        {!! Form::close() !!}
      @endif
    </section>

    @if ($shop->requiresBusinessDocuments())
    <section class="mp-verify-card">
      <header class="mp-verify-card__head">
        <div>
          <h2>{{ trans('app.store_document_verification') }}</h2>
          <p>{{ trans('messages.verification_tab_store_documents_help') }}</p>
        </div>
        @if ($hasStoreDocs)
          <span class="mp-chip mp-chip--info">{{ trans('app.submitted') }}</span>
        @endif
      </header>

      <p class="mp-verify-card__note">{!! trans('messages.verification_store_documents') !!}</p>

      @include('merchant.verify.partials.documents', [
        'config' => $config,
        'editable' => $canSubmit,
        'attachments' => $storeAttachments,
        'documentType' => 'store',
      ])
    </section>
    @endif
  </div>
</div>
