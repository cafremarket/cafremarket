<div class="modal-dialog modal-sm">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('packages.wallet.transfer_balance') }}
    </div>
    {!! Form::open(['route' => 'merchant.wallet.transfer', 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-body">

      <div class="form-group space30">
        {!! Form::label('order', trans('packages.wallet.amount')) !!}
        <div class="input-group">
          @if (get_currency_prefix())
            <span class="input-group-addon">
              {{ get_currency_prefix() }}
            </span>
          @endif

          {!! Form::number('amount', null, ['class' => 'form-control', 'step' => 'any', 'placeholder' => trans('packages.wallet.amount'), 'max' => $wallet->balance, 'required']) !!}

          @if (get_currency_suffix())
            <span class="input-group-addon">
              {{ get_currency_suffix() }}
            </span>
          @endif
        </div>
        <div class="help-block with-errors">{{ trans('packages.wallet.max_transfer_amount', ['amount' => get_formated_currency($wallet->balance, 2)]) }}</div>
      </div>

    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('packages.wallet.transfer'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div> <!-- / .modal-content -->
</div> <!-- / .modal-dialog -->
