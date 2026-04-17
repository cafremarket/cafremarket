<div class="modal-dialog modal-sm">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('packages.wallet.payout_request') }}
    </div>

    <div class="modal-body">
      <p class="lead">
        {{ trans('packages.wallet.available_balance') }}:
        <strong>{{ get_formated_currency($balance, 2, config('system_settings.currency.id')) }}</strong>
      </p>

      @if ($balance < $minimum)
        <div class="alert alert-info" role="alert">
          <h4><i class="fa fa-warning"></i> {{ trans('packages.wallet.alert') }}!</h4>
          {!! trans('packages.wallet.minimum_withdrawal_limit_amount', ['amount' => get_formated_currency($minimum, 2, config('system_settings.currency.id'))]) !!}
        </div>
      @else
        {!! Form::open(['route' => auth()->guard('affiliate')->check() ? 'affiliate.wallet.withdraw' : 'merchant.wallet.withdraw', 'files' => true, 'id' => 'form', 'data-toggle' => 'validator']) !!}
        <div class="form-group">
          {{-- {!! Form::label('order', trans('packages.wallet.amount')) !!} --}}
          <div class="input-group">
            @if (get_currency_prefix())
              <span class="input-group-addon" id="basic-addon1">
                {{ get_currency_prefix() }}
              </span>
            @endif

            {!! Form::number('amount', null, ['class' => 'form-control input-lg', 'step' => 'any', 'min' => $minimum, 'max' => $balance, 'placeholder' => trans('packages.wallet.amount')]) !!}

            @if (get_currency_suffix())
              <span class="input-group-addon" id="basic-addon1">
                {{ get_currency_suffix() }}
              </span>
            @endif
          </div>
          <div class="help-block with-errors">
            {!! trans('packages.wallet.minimum_withdrawal_limit_amount', ['amount' => get_formated_currency($minimum, 2, config('system_settings.currency.id'))]) !!}
          </div>
        </div>

        <p class="text-info">
          <i class="fa fa-info-circle"></i>
          {!! trans('packages.wallet.payout_fee_may_apply', ['platform' => get_platform_title()]) !!}
        </p>

        {!! Form::submit(trans('packages.wallet.submit'), ['class' => 'btn btn-flat btn-new pull-right']) !!}
        {!! Form::close() !!}
      @endif
    </div><!-- / .modal-body -->
    <div class="modal-footer">
    </div>
  </div> <!-- / .modal-content -->
</div> <!-- / .modal-dialog -->
