<div class="col-md-6 col-md-offset-2 my-5">
  <div class="panel panel-default">
    <div class="panel-heading">{{ trans('packages.wallet.transfer_balance') }}</div>
    <div class="panel-body">
      {!! Form::open(['route' => 'customer.account.wallet.transfer', 'id' => 'form', 'data-toggle' => 'validator']) !!}

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

      <button id="pay-now-btn" class="btn btn-primary btn-lg btn-block" type="submit">
        <small><i class="fa fa-shield"></i>
          <span id="pay-now-btn-txt">@lang('packages.wallet.transfer_self_merchant')</span>
        </small>
      </button>
      {!! Form::close() !!}
    </div>
  </div>
</div>
