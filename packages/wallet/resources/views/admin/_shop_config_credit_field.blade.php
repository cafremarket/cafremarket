<div class="form-group">
  {!! Form::label('credit_back_percentage', trans('packages.wallet.wallet_credit_back_percentage') . ': *', ['class' => 'with-help col-sm-4 control-label']) !!}

  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('packages.wallet.help_wallet_credit_back_config') }}"></i>

  <div class="col-sm-7 nopadding-left">
    @if ($can_update)
      <div class="input-group">
        {!! Form::number('credit_back_percentage', get_formated_decimal($config->credit_back_percentage), ['class' => 'form-control', 'min' => 0, 'placeholder' => trans('packages.wallet.wallet_credit_back_percentage'), 'required']) !!}

        <span class="input-group-addon" id="basic-addon1">{{ trans('app.%') }}</span>
      </div>
    @else
      <span>{{ get_formated_decimal($config->credit_back_percentage) }}</span>
    @endif
    <div class="help-block with-errors"></div>
  </div>
</div> <!-- /.form-group -->
