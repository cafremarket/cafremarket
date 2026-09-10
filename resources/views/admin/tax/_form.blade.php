<div class="form-group">
  {!! Form::label('name', trans('app.form.name').'*') !!}
  {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.name'), 'required']) !!}
  <div class="help-block with-errors"></div>
</div>

<div class="row">
  <div class="col-md-6 nopadding-right">
    <div class="form-group">
      {!! Form::label('type', (trans('app.form.type') ?? 'Type').'*') !!}
      {!! Form::select('type', [
        'percent' => (trans('app.percent') ?? 'Percentage'),
        'fixed' => (trans('app.fixed') ?? 'Fixed'),
      ], isset($tax) ? ($tax->type ?: 'percent') : 'percent', [
        'class' => 'form-control select2-normal',
        'id' => 'tax_type',
        'required',
      ]) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
  <div class="col-md-6 nopadding-left">
    <div class="form-group">
      {!! Form::label('taxrate', trans('app.form.taxrate').'*') !!}
      <div class="input-group">
        {!! Form::number('taxrate', null, ['class' => 'form-control', 'step' => 'any', 'min' => 0, 'placeholder' => trans('app.placeholder.tax_rate'), 'required']) !!}
        <span class="input-group-addon" id="taxrate_addon"> % </span>
      </div>
      <div class="help-block with-errors"></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6 nopadding-right">
    <div class="form-group">
      {!! Form::label('active', trans('app.form.status').'*') !!}
      {!! Form::select('active', ['1' => trans('app.active'), '0' => trans('app.inactive')], null, ['class' => 'form-control select2-normal', 'placeholder' => trans('app.placeholder.status'), 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6 nopadding-right">
    <div class="form-group">
      {!! Form::label('country_id', trans('app.form.country').'*') !!}
      {!! Form::select('country_id', $countries , isset($tax) ? $tax->country_id : config('system_settings.address_default_country'), ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.country'), 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
  <div class="col-md-6 nopadding-left">
    <div class="form-group">
      {!! Form::label('state_id', trans('app.form.state')) !!}
      {!! Form::select('state_id', $states , isset($tax) ? $tax->state_id : config('system_settings.address_default_state'), ['class' => 'form-control select2-tag', 'placeholder' => trans('app.placeholder.state')]) !!}
    </div>
  </div>
</div>

<p class="help-block">* {{ trans('app.form.required_fields') }}</p>

<script>
  (function () {
    function syncTaxAddon() {
      var type = document.getElementById('tax_type');
      var addon = document.getElementById('taxrate_addon');
      if (!type || !addon) return;
      addon.textContent = type.value === 'fixed'
        ? (' {{ get_currency_symbol() }} ')
        : ' % ';
    }
    document.addEventListener('DOMContentLoaded', function () {
      var type = document.getElementById('tax_type');
      if (type) {
        type.addEventListener('change', syncTaxAddon);
        syncTaxAddon();
      }
    });
    // Ajax modal may inject this form after DOMContentLoaded.
    syncTaxAddon();
    var type = document.getElementById('tax_type');
    if (type && !type.dataset.taxAddonBound) {
      type.dataset.taxAddonBound = '1';
      type.addEventListener('change', syncTaxAddon);
    }
  })();
</script>
