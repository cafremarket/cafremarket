<div class="form-group">
  {!! Form::label('credit_back_percentage', trans('packages.wallet.wallet_credit_back_percentage'), ['class' => 'with-help']) !!}
  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('packages.wallet.help_wallet_credit_back_inventory_form') }}"></i>
  <div class="input-group">
    {!! Form::number('credit_back_percentage', null, ['class' => 'form-control', 'placeholder' => trans('packages.wallet.wallet_credit_back_percentage')]) !!}

    <span class="input-group-addon" id="basic-addon1">{{ trans('app.%') }}</span>
  </div>
  <div class="help-block with-errors">
    <small class="text-info">
      <em class="fa fa-info-circle"> {{ trans('packages.wallet.when_empty_default_credit_reward_is_used') }}</em>
    </small>
  </div>
</div> <!-- /.form-group -->
