@include('theme::partials._address_modal_assets')

<div class="modal-dialog modal-lg modal-dialog-centered sf-address-create-modal" role="document">
  <div class="modal-content sf-address-modal">
    <div class="modal-header sf-address-modal__header border-0">
      <div class="sf-address-modal__head">
        <span class="sf-address-modal__icon" aria-hidden="true">
          <i class="fa fa-map-marker"></i>
        </span>
        <div>
          <h4 class="sf-address-modal__title">{{ trans('theme.button.add_new_address') }}</h4>
          <p class="sf-address-modal__subtitle">{{ trans('theme.set_delivery_location') }}</p>
        </div>
      </div>
      <button type="button" class="close sf-address-modal__close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>

    <div class="modal-body sf-address-modal__body">
      {!! Form::open(['route' => 'my.address.save', 'class' => 'sf-form', 'data-toggle' => 'validator']) !!}
        @include('partials.address_wizard', [
          'wizardId' => 'create-address-wizard',
          'countries' => $countries,
          'states' => $states,
          'address_types' => $address_types,
        ])
      {!! Form::close() !!}

      <p class="sf-address-modal__required">* {{ trans('theme.help.required_fields') }}</p>
    </div>
  </div>
</div>
