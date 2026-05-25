<div class="modal-dialog">
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
        {!! Form::open(['route' => auth()->guard('affiliate')->check() ? 'affiliate.wallet.withdraw' : 'merchant.wallet.withdraw', 'files' => true, 'id' => 'payout-request-form', 'data-toggle' => 'validator']) !!}

        <div class="form-group">
          <label>{{ trans('packages.wallet.payout_method') }}</label>
          {!! Form::select('payout_method', [
            'bank_transfer' => trans('packages.wallet.payout_method_bank_transfer'),
            'mpesa' => trans('packages.wallet.payout_method_mpesa'),
            'emola' => trans('packages.wallet.payout_method_emola'),
          ], old('payout_method', 'bank_transfer'), ['class' => 'form-control', 'id' => 'payout-method-select', 'required']) !!}
        </div>

        <div id="payout-details-bank" class="payout-method-panel">
          <div class="form-group">
            {!! Form::label('payout_bank_name', trans('packages.wallet.payout_bank_name')) !!}
            {!! Form::text('payout_bank_name', null, ['class' => 'form-control payout-detail-field', 'data-method' => 'bank_transfer', 'placeholder' => trans('packages.wallet.payout_bank_name')]) !!}
          </div>
          <div class="form-group">
            {!! Form::label('payout_account_holder', trans('packages.wallet.payout_account_holder')) !!}
            {!! Form::text('payout_account_holder', null, ['class' => 'form-control payout-detail-field', 'data-method' => 'bank_transfer', 'placeholder' => trans('packages.wallet.payout_account_holder')]) !!}
          </div>
          <div class="form-group">
            {!! Form::label('payout_account_number', trans('packages.wallet.payout_account_number')) !!}
            {!! Form::text('payout_account_number', null, ['class' => 'form-control payout-detail-field', 'data-method' => 'bank_transfer', 'placeholder' => trans('packages.wallet.payout_account_number')]) !!}
          </div>
        </div>

        <div id="payout-details-mobile" class="payout-method-panel" style="display: none;">
          <div class="form-group">
            {!! Form::label('payout_mobile', trans('packages.wallet.payout_mobile_number')) !!}
            {!! Form::text('payout_mobile', null, ['class' => 'form-control payout-detail-field', 'data-method' => 'mobile', 'placeholder' => '258XXXXXXXXX']) !!}
            <p class="help-block small text-muted">{{ trans('packages.wallet.payout_mobile_help') }}</p>
          </div>
        </div>

        @if (!empty($existing_instruction))
          <p class="text-muted small">
            <i class="fa fa-info-circle"></i>
            {{ trans('packages.wallet.payout_saved_instruction') }}: {{ $existing_instruction }}
          </p>
        @endif

        <div class="form-group">
          <div class="input-group">
            @if (get_currency_prefix())
              <span class="input-group-addon">{{ get_currency_prefix() }}</span>
            @endif
            {!! Form::number('amount', null, ['class' => 'form-control input-lg', 'id' => 'payout-amount', 'step' => 'any', 'min' => $minimum, 'max' => $balance, 'placeholder' => trans('packages.wallet.amount'), 'required']) !!}
            @if (get_currency_suffix())
              <span class="input-group-addon">{{ get_currency_suffix() }}</span>
            @endif
          </div>
          <div class="help-block with-errors">
            {!! trans('packages.wallet.minimum_withdrawal_limit_amount', ['amount' => get_formated_currency($minimum, 2, config('system_settings.currency.id'))]) !!}
          </div>
        </div>

        <p class="text-info">
          <i class="fa fa-info-circle"></i>
          {!! trans('packages.wallet.payout_sales_commission_already_deducted', ['platform' => get_platform_title()]) !!}
        </p>

        {!! Form::submit(trans('packages.wallet.submit'), ['class' => 'btn btn-flat btn-new pull-right']) !!}
        {!! Form::close() !!}
      @endif
    </div>
    <div class="modal-footer"></div>
  </div>
</div>

<script>
  (function () {
    var $form = $('#payout-request-form');
    if (!$form.length) {
      return;
    }

    var $method = $('#payout-method-select');

    function togglePayoutPanels() {
      var method = $method.val();
      var isMobile = method === 'mpesa' || method === 'emola';
      $('#payout-details-bank').toggle(!isMobile);
      $('#payout-details-mobile').toggle(isMobile);
      $('#payout-details-bank .payout-detail-field').prop('required', !isMobile);
      $('#payout-details-mobile .payout-detail-field').prop('required', isMobile);
    }

    $method.on('change', togglePayoutPanels);

    togglePayoutPanels();
  })();
</script>
