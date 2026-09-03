<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('packages.wallet.create_wallet') }}
    </div>

    {!! Form::open(['route' => 'admin.wallet.create.submit', 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-body">
      <p class="text-muted">{{ trans('packages.wallet.create_wallet_help') }}</p>

      <div class="form-group">
        {!! Form::label('user_type', trans('packages.wallet.type')) !!}
        {!! Form::select('user_type', [
          'customer' => trans('app.customer'),
          'merchant' => trans('packages.wallet.shop'),
        ], old('user_type', 'customer'), ['class' => 'form-control', 'required']) !!}
        <div class="help-block with-errors"></div>
      </div>

      <div class="form-group">
        {!! Form::label('email', trans('app.email')) !!}
        {!! Form::email('email', old('email'), [
          'class' => 'form-control',
          'placeholder' => trans('packages.wallet.transfer_to_wallet'),
          'required',
        ]) !!}
        <div class="help-block with-errors"></div>
      </div>
    </div>

    <div class="modal-footer">
      {!! Form::submit(trans('packages.wallet.create_wallet'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
