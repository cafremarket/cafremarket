@include('theme::partials._address_modal_assets')

<div class="modal-dialog modal-lg modal-dialog-centered sf-address-select-modal" role="document">
  <div class="modal-content p-2">
    <div class="modal-header p-3 border-0 sf-address-select-modal__header">
      <h4 class="modal-title flex-grow-1 text-center mb-0 pr-4">{{ trans('theme.select_delivery_address') }}</h4>
      <button type="button" class="close sf-address-select-modal__close" data-dismiss="modal" aria-label="{{ trans('theme.button.cancel') }}">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>

    <div class="modal-body pt-0">
      <div class="sf-address-select-toolbar">
        <p class="sf-address-toolbar__hint mb-0">{{ trans('theme.set_delivery_location') }}</p>
      </div>

      <div class="sf-address-grid sf-address-grid--select mt-3">
        @forelse($addresses as $address)
          <button
            type="button"
            class="sf-address-card sf-address-card--selectable js-use-delivery-address{{ $activeAddressId === $address->id ? ' sf-address-card--active' : '' }}"
            data-address-id="{{ $address->id }}"
          >
            <div class="sf-address-card__head">
              <span class="sf-address-card__type">{{ $address->address_type }}</span>
              @if ($activeAddressId === $address->id)
                <span class="sf-address-card__badge">
                  <i class="fa fa-check-circle" aria-hidden="true"></i> {{ trans('theme.current') }}
                </span>
              @endif
            </div>
            <div class="sf-address-card__body text-left">
              {!! $address->toHtml() !!}
            </div>
          </button>
        @empty
          <div class="sf-empty-state">
            <i class="fa fa-map" aria-hidden="true"></i>
            <p>{{ trans('theme.nothing_found') }}</p>
            <p class="text-muted">{{ trans('theme.add_delivery_address_help') }}</p>
          </div>
        @endforelse
      </div>
    </div>

    <div class="modal-footer border-0 pt-0 px-3 pb-3 justify-content-between">
      <button type="button" class="btn btn-default flat js-close-address-select" data-dismiss="modal">
        {{ trans('theme.button.cancel') }}
      </button>
      <a href="{{ route('my.address.create') }}" class="modalAction btn sf-btn-primary sf-address-select-modal__add-btn">
        <i class="fa fa-plus" aria-hidden="true"></i>
        <span>{{ trans('theme.button.add_new_address') }}</span>
      </a>
    </div>
  </div>
</div>
