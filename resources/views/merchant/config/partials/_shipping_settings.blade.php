{{-- Shop default shipping (free / fixed / km) --}}
<div class="form-group">
  {!! Form::label('shipping_type', (trans('app.shipping') ?? 'Shipping').' type:', ['class' => 'with-help col-sm-4 control-label']) !!}
  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="Shop default shipping. Products can override this."></i>
  <div class="col-sm-7 nopadding-left">
    @if ($can_update)
      {!! Form::select('shipping_type', [
        'free' => trans('theme.free_shipping') ?: 'Free shipping',
        'fixed' => 'Fixed charge',
        'km' => 'Per kilometre (distance)',
      ], $config->shipping_type ?? 'fixed', ['class' => 'form-control select2-normal', 'id' => 'shop_shipping_type']) !!}
    @else
      <span>{{ $config->shipping_type ?? 'fixed' }}</span>
    @endif
  </div>
</div>

<div class="form-group shop-ship-fixed">
  {!! Form::label('shipping_fixed_rate', 'Fixed shipping rate:', ['class' => 'with-help col-sm-4 control-label']) !!}
  <div class="col-sm-7 nopadding-left">
    @if ($can_update)
      <div class="input-group">
        @if (get_currency_prefix())
          <span class="input-group-addon">{{ get_currency_prefix() }}</span>
        @endif
        {!! Form::number('shipping_fixed_rate', get_formated_decimal($config->shipping_fixed_rate), ['class' => 'form-control', 'min' => 0, 'step' => 'any', 'placeholder' => '0.00']) !!}
        @if (get_currency_suffix())
          <span class="input-group-addon">{{ get_currency_suffix() }}</span>
        @endif
      </div>
    @else
      <span>{{ get_formated_decimal($config->shipping_fixed_rate) }}</span>
    @endif
  </div>
</div>

<div class="form-group shop-ship-km">
  {!! Form::label('shipping_base_fee', 'KM base fee:', ['class' => 'with-help col-sm-4 control-label']) !!}
  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="Optional base amount added before per-km charge."></i>
  <div class="col-sm-7 nopadding-left">
    @if ($can_update)
      <div class="input-group">
        @if (get_currency_prefix())
          <span class="input-group-addon">{{ get_currency_prefix() }}</span>
        @endif
        {!! Form::number('shipping_base_fee', get_formated_decimal($config->shipping_base_fee), ['class' => 'form-control', 'min' => 0, 'step' => 'any', 'placeholder' => '0.00']) !!}
        @if (get_currency_suffix())
          <span class="input-group-addon">{{ get_currency_suffix() }}</span>
        @endif
      </div>
    @else
      <span>{{ get_formated_decimal($config->shipping_base_fee) }}</span>
    @endif
  </div>
</div>

<div class="form-group shop-ship-km">
  {!! Form::label('shipping_per_km_rate', 'Rate per KM:', ['class' => 'with-help col-sm-4 control-label']) !!}
  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="Charge = base fee + (distance km × rate). Cart uses the highest product shipping amount."></i>
  <div class="col-sm-7 nopadding-left">
    @if ($can_update)
      <div class="input-group">
        @if (get_currency_prefix())
          <span class="input-group-addon">{{ get_currency_prefix() }}</span>
        @endif
        {!! Form::number('shipping_per_km_rate', get_formated_decimal($config->shipping_per_km_rate), ['class' => 'form-control', 'min' => 0, 'step' => 'any', 'placeholder' => '0.00']) !!}
        @if (get_currency_suffix())
          <span class="input-group-addon">{{ get_currency_suffix() }}</span>
        @endif
      </div>
    @else
      <span>{{ get_formated_decimal($config->shipping_per_km_rate) }}</span>
    @endif
  </div>
</div>

@if ($can_update)
  <script>
    (function () {
      function toggleShopShipFields() {
        var t = document.getElementById('shop_shipping_type');
        if (!t) return;
        var v = t.value;
        document.querySelectorAll('.shop-ship-fixed').forEach(function (el) {
          el.style.display = v === 'fixed' ? '' : 'none';
        });
        document.querySelectorAll('.shop-ship-km').forEach(function (el) {
          el.style.display = v === 'km' ? '' : 'none';
        });
      }
      document.addEventListener('DOMContentLoaded', function () {
        var t = document.getElementById('shop_shipping_type');
        if (t) {
          t.addEventListener('change', toggleShopShipFields);
          toggleShopShipFields();
        }
      });
    })();
  </script>
@endif
