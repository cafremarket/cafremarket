<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('packages.wallet.topup_wallet') }}
    </div>

    {!! Form::open(['route' => 'admin.wallet.topup.submit', 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('user_type', trans('packages.wallet.type')) !!}
        {!! Form::select('user_type', [
          'customer' => trans('app.customer'),
          'merchant' => trans('packages.wallet.shop'),
        ], $userType ?? 'customer', ['class' => 'form-control', 'required']) !!}
        <div class="help-block with-errors"></div>
      </div>

      <div class="form-group">
        {!! Form::label('email', trans('app.email')) !!}
        {!! Form::email('email', $email ?? null, [
          'class' => 'form-control',
          'placeholder' => trans('packages.wallet.transfer_to_wallet'),
          'required',
        ]) !!}
        <div class="help-block with-errors"></div>
      </div>

      <div class="form-group">
        {!! Form::label('amount', trans('packages.wallet.amount')) !!}
        <div class="input-group">
          @if (get_currency_prefix())
            <span class="input-group-addon">{{ get_currency_prefix() }}</span>
          @endif
          {!! Form::number('amount', old('amount'), [
            'class' => 'form-control',
            'step' => 'any',
            'min' => '0.01',
            'placeholder' => trans('packages.wallet.amount'),
            'required',
          ]) !!}
          @if (get_currency_suffix())
            <span class="input-group-addon">{{ get_currency_suffix() }}</span>
          @endif
        </div>
        <div class="help-block with-errors"></div>
      </div>

      <div class="form-group">
        {!! Form::label('description', trans('packages.wallet.description')) !!}
        {!! Form::text('description', old('description'), [
          'class' => 'form-control',
          'placeholder' => trans('packages.wallet.admin_topup_description'),
        ]) !!}
        <div class="help-block with-errors"></div>
      </div>
    </div>

    <div class="modal-footer">
      {!! Form::submit(trans('packages.wallet.topup_wallet'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
