@php
  $prefix = $prefix ?? 'platform_fee';
  $title = $title ?? '';
  $enabledKey = $prefix.'_enabled';
  $typeKey = $prefix.'_type';
  $valueKey = $prefix.'_value';
  $enabled = (int) get_from_option_table($enabledKey, 0);
  $type = get_from_option_table($typeKey, 'flat');
  $value = get_from_option_table($valueKey, 0);
@endphp

<div class="panel panel-default">
  <div class="panel-heading"><strong>{{ $title }}</strong></div>
  <div class="panel-body">
    <div class="form-group">
      <div class="col-sm-5 text-right">
        {!! Form::label($enabledKey, trans('packages.wallet.platform_fee_enabled') . ':', ['class' => 'control-label']) !!}
      </div>
      <div class="col-sm-7 nopadding-left">
        {!! Form::select($enabledKey, ['0' => trans('app.no'), '1' => trans('app.yes')], $enabled, ['class' => 'form-control']) !!}
      </div>
    </div>
    <div class="form-group">
      <div class="col-sm-5 text-right">
        {!! Form::label($typeKey, trans('packages.wallet.platform_fee_type') . ':', ['class' => 'control-label']) !!}
      </div>
      <div class="col-sm-7 nopadding-left">
        {!! Form::select($typeKey, [
          'flat' => trans('packages.wallet.platform_fee_type_flat'),
          'percent' => trans('packages.wallet.platform_fee_type_percent'),
        ], $type, ['class' => 'form-control platform-fee-type-select']) !!}
      </div>
    </div>
    <div class="form-group">
      <div class="col-sm-5 text-right">
        {!! Form::label($valueKey, trans('packages.wallet.platform_fee_value') . ':', ['class' => 'control-label']) !!}
      </div>
      <div class="col-sm-7 nopadding-left">
        @php
          $flatSymbol = config('system_settings.currency.symbol', 'MT');
        @endphp
        <div class="input-group">
          <span
            class="input-group-addon platform-fee-value-addon"
            data-flat-symbol="{{ $flatSymbol }}"
            data-percent-symbol="%"
          >{{ $type === 'percent' ? '%' : $flatSymbol }}</span>
          {!! Form::number($valueKey, $value, ['class' => 'form-control', 'min' => 0, 'step' => 'any']) !!}
        </div>
        <p class="help-block small text-muted">{{ trans('packages.wallet.platform_fee_type_percent') }}: use 2.5 for 2.5%. {{ trans('packages.wallet.platform_fee_type_flat') }}: fixed MZN per transaction.</p>
      </div>
    </div>
  </div>
</div>
