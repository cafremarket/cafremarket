@php
  $currencySymbol = config('system_settings.currency.symbol', 'MT');
  $transactionFeeType = old('transaction_fee_type', optional($subscriptionPlan ?? null)->transaction_fee_type ?? 'flat');
  $marketplaceCommissionType = old('marketplace_commission_type', optional($subscriptionPlan ?? null)->marketplace_commission_type ?? 'percent');
@endphp

<div class="form-group">
  {!! Form::label('name', trans('app.form.name') . '*', ['class' => 'with-help']) !!}
  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.subscription_name') }}"></i>
  {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.subscription_name'), 'required']) !!}
  <div class="help-block with-errors"></div>
</div>

<div class="form-group">
  {!! Form::label('plan_id', trans('app.form.subscription_plan_id') . '*', ['class' => 'with-help']) !!}
  {!! Form::text('plan_id', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.subscription_plan_id'), 'required']) !!}
  <div class="help-block with-errors"><small class="text-info"><i class="fa fa-info-circle"></i> {!! trans('help.subscription_plan_id') !!}</small></div>
</div>

<div class="row">
  <div class="col-md-6 nopadding-right">
    <div class="form-group">
      {!! Form::label('cost', trans('app.form.cost_per_month') . '*', ['class' => 'with-help']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.subscription_cost') }}"></i>
      <div class="input-group">
        @if (get_currency_prefix())
          <span class="input-group-addon">{{ get_currency_prefix() }}</span>
        @endif
        {!! Form::number('cost', null, ['class' => 'form-control', 'step' => 'any', 'placeholder' => trans('app.placeholder.subscription_cost'), 'required']) !!}
        @if (get_currency_suffix())
          <span class="input-group-addon">{{ get_currency_suffix() }}</span>
        @endif
      </div>
      <div class="help-block with-errors"></div>
    </div>
  </div>
  <div class="col-md-6 nopadding-left">
    <label class="with-help">&nbsp;</label>
    <div class="form-group">
      <div class="input-group">
        {{ Form::hidden('featured', 0) }}
        {!! Form::checkbox('featured', null, null, ['id' => 'featured', 'class' => 'icheckbox_line']) !!}
        {!! Form::label('featured', trans('app.form.featured')) !!}
        <span class="input-group-addon">
          <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.featured_subscription') }}"></i>
        </span>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6 nopadding-right">
    <div class="form-group">
      {!! Form::label('team_size', trans('app.form.team_size') . '*', ['class' => 'with-help']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.team_size') }}"></i>
      <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-users"></i></span>
        {!! Form::number('team_size', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.team_size'), 'required']) !!}
      </div>
      <div class="help-block with-errors"></div>
    </div>
  </div>
  <div class="col-md-6 nopadding-left">
    <div class="form-group">
      {!! Form::label('inventory_limit', trans('app.form.inventory_limit') . '*', ['class' => 'with-help']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.inventory_limit') }}"></i>
      <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-cubes"></i></span>
        {!! Form::number('inventory_limit', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.inventory_limit'), 'required']) !!}
      </div>
      <div class="help-block with-errors"></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6 nopadding-right">
    <div class="form-group">
      {!! Form::label('marketplace_commission_type', trans('app.form.marketplace_commission_type') . '*', ['class' => 'with-help']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.marketplace_commission') }}"></i>
      {!! Form::select('marketplace_commission_type', [
        'percent' => trans('packages.wallet.platform_fee_type_percent'),
        'flat' => trans('packages.wallet.platform_fee_type_flat'),
      ], $marketplaceCommissionType, ['class' => 'form-control subscription-fee-type-select', 'data-target' => 'marketplace_commission']) !!}
    </div>
    <div class="form-group">
      {!! Form::label('marketplace_commission', trans('app.form.marketplace_commission') . '*', ['class' => 'with-help']) !!}
      <div class="input-group">
        {!! Form::number('marketplace_commission', null, ['class' => 'form-control', 'step' => 'any', 'placeholder' => trans('app.placeholder.marketplace_commission'), 'required']) !!}
        <span
          class="input-group-addon subscription-fee-value-addon"
          data-for="marketplace_commission"
          data-flat-symbol="{{ $currencySymbol }}"
          data-percent-symbol="%"
        >{{ $marketplaceCommissionType === 'flat' ? $currencySymbol : '%' }}</span>
      </div>
      <div class="help-block with-errors"><small class="text-warning"><i class="fa fa-warning"></i> {!! trans('help.subscription_marketplace_commission_type') !!}</small></div>
    </div>
  </div>
  <div class="col-md-6 nopadding-left">
    <div class="form-group">
      {!! Form::label('transaction_fee_type', trans('app.form.transaction_fee_type') . '*', ['class' => 'with-help']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.transaction_fee') }}"></i>
      {!! Form::select('transaction_fee_type', [
        'flat' => trans('packages.wallet.platform_fee_type_flat'),
        'percent' => trans('packages.wallet.platform_fee_type_percent'),
      ], $transactionFeeType, ['class' => 'form-control subscription-fee-type-select', 'data-target' => 'transaction_fee']) !!}
    </div>
    <div class="form-group">
      {!! Form::label('transaction_fee', trans('app.form.transaction_fee') . '*', ['class' => 'with-help']) !!}
      <div class="input-group">
        @if (get_currency_prefix())
          <span class="input-group-addon subscription-fee-prefix" data-for="transaction_fee" style="{{ $transactionFeeType === 'percent' ? 'display:none' : '' }}">{{ get_currency_prefix() }}</span>
        @endif
        {!! Form::number('transaction_fee', null, ['class' => 'form-control', 'step' => 'any', 'placeholder' => trans('app.placeholder.transaction_fee'), 'required']) !!}
        <span
          class="input-group-addon subscription-fee-value-addon"
          data-for="transaction_fee"
          data-flat-symbol="{{ $currencySymbol }}"
          data-percent-symbol="%"
        >{{ $transactionFeeType === 'percent' ? '%' : ($currencySymbol) }}</span>
        @if (get_currency_suffix())
          <span class="input-group-addon subscription-fee-suffix" data-for="transaction_fee" style="{{ $transactionFeeType === 'percent' ? 'display:none' : '' }}">{{ get_currency_suffix() }}</span>
        @endif
      </div>
      <div class="help-block with-errors"><small class="text-warning"><i class="fa fa-warning"></i> {!! trans('help.subscription_transaction_fee_type') !!}</small></div>
    </div>
  </div>
</div>

<div class="form-group">
  {!! Form::label('best_for', trans('app.form.best_for'), ['class' => 'with-help']) !!}
  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.subscription_best_for') }}"></i>
  {!! Form::text('best_for', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.subscription_best_for')]) !!}
</div>

<p class="help-block">* {{ trans('app.form.required_fields') }}</p>

<script>
  (function () {
    function syncSubscriptionFeeAddon(select) {
      var target = select.getAttribute('data-target');
      if (!target) return;
      var isPercent = select.value === 'percent';
      var addon = document.querySelector('.subscription-fee-value-addon[data-for="' + target + '"]');
      if (addon) {
        addon.textContent = isPercent ? addon.getAttribute('data-percent-symbol') : addon.getAttribute('data-flat-symbol');
      }
      document.querySelectorAll('.subscription-fee-prefix[data-for="' + target + '"], .subscription-fee-suffix[data-for="' + target + '"]').forEach(function (el) {
        el.style.display = isPercent ? 'none' : '';
      });
    }
    document.querySelectorAll('.subscription-fee-type-select').forEach(function (select) {
      syncSubscriptionFeeAddon(select);
      select.addEventListener('change', function () {
        syncSubscriptionFeeAddon(select);
      });
    });
  })();
</script>
