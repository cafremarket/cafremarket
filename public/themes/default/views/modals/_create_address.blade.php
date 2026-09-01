<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
  <div class="modal-content p-2">
    <div class="modal-header p-3 border-0">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="modal-body pt-0">
      <div class="form-title text-center mb-2">
        <h4>{{ trans('theme.button.add_new_address') }}</h4>
      </div>

      {!! Form::open(['route' => 'my.address.save', 'data-toggle' => 'validator']) !!}
        @include('partials.address_wizard', [
          'wizardId' => 'create-address-wizard',
          'countries' => $countries,
          'states' => $states,
          'address_types' => $address_types,
        ])
      {!! Form::close() !!}

      <small class="help-block text-muted text-left mt-3">* {{ trans('theme.help.required_fields') }}</small>
    </div>
  </div>
</div>
