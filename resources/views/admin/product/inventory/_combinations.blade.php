<legend>{{ trans('app.variants') }}</legend>
<p class="help-block">{{ trans('help.default_variant_selection') }}</p>
<table class="table table-default table-variants-editor" id="variantsTable">
  <thead>
    <tr>
      <th>{{ trans('app.sl_number') }}</th>

      <th>{{ trans('app.is_default') }}
        <small class="text-muted" data-toggle="tooltip" data-placement="top" title="{{ trans('help.default_variant_selection') }}"><sup><i class="fa fa-question"></i></sup></small>
      </th>

      <th>{{ trans('app.form.variants') }}
        <small class="text-muted" data-toggle="tooltip" data-placement="top" title="{{ trans('help.variants') }}"><sup><i class="fa fa-question"></i></sup></small>
      </th>

      <th>
        {{ trans('app.form.image') }}
        <small class="text-muted" data-toggle="tooltip" data-placement="top" title="{{ trans('help.variant_image') }}"><sup><i class="fa fa-question"></i></sup></small>
      </th>

      <th>
        {{ trans('app.form.sku') }}
        <small class="text-muted" data-toggle="tooltip" data-placement="top" title="{{ trans('help.sku') }}"><sup><i class="fa fa-question"></i></sup></small>
      </th>

      <th>
        {{ trans('app.form.stock_quantity') }}
        <small class="text-muted" data-toggle="tooltip" data-placement="top" title="{{ trans('help.stock_quantity') }}"><sup><i class="fa fa-question"></i></sup></small>
      </th>

      {{-- @if (config('system_settings.show_item_conditions'))
        <th>
          {{ trans('app.form.condition') }}
          <small class="text-muted" data-toggle="tooltip" data-placement="top" title="{{ trans('help.seller_product_condition') }}"><sup><i class="fa fa-question"></i></sup></small>
        </th>
      @endif

      <th>
        {{ trans('app.form.purchase_price') }}
        <small class="text-muted" data-toggle="tooltip" data-placement="top" title="{{ trans('help.purchase_price') }}"><sup><i class="fa fa-question"></i></sup></small>
      </th> --}}

      <th>
        {{ trans('app.form.sale_price') }}
        <small class="text-muted" data-toggle="tooltip" data-placement="top" title="{{ trans('help.sale_price') }}"><sup><i class="fa fa-question"></i></sup></small>
      </th>

      <th><i class="fa fa-trash-o"></i></th>
    </tr>
  </thead>

  <tbody>
    @foreach ($combinations as $combination)
      <tr class="variant-row">
        <td>
          <div class="form-group">{{ $loop->iteration }}</div>
        </td>

        <td class="text-center">
          <div class="form-group" style="margin-top: 8px;">
            <label class="radio-inline" title="{{ trans('help.default_variant_selection') }}">
              <input type="radio" name="default_variant" value="{{ $loop->index }}" {{ $loop->first ? 'checked' : '' }}>
              {{ trans('app.is_default') }}
            </label>
          </div>
        </td>

        <td>
          <div class="form-group">
            @foreach ($combination as $attrId => $attrValue)
              {{ Form::hidden('variants[' . $loop->parent->index . '][' . $attrId . ']', key($attrValue)) }}

              <span class="label label-primary">{{ current($attrValue) }}</span>
            @endforeach
          </div>
          <small class="text-muted">{{ trans('help.each_variant_own_inventory') }}</small>
        </td>

        <td>
          <label class="img-btn-with-preview">
            {{ Form::file('variant_images[' . $loop->index . ']', ['class' => 'variant-img']) }}
            <img src="{{ url('images/placeholders/no_img.png') }}" class="img-md" alt="variant-{{ $loop->index }}">
          </label>
        </td>

        <td>
          <div class="form-group">
            {!! Form::text('skus[' . $loop->index . ']', null, ['class' => 'form-control variant-sku', 'placeholder' => trans('app.placeholder.sku'), 'required']) !!}
          </div>
        </td>

        <td>
          <div class="form-group">
            @include('admin.partials._qty_stepper', [
              'name' => 'stock_quantities[' . $loop->index . ']',
              'value' => null,
              'class' => 'form-control variant-qtt',
              'min' => 0,
              'step' => '1',
              'required' => true,
              'placeholder' => trans('app.placeholder.stock_quantity'),
            ])
          </div>
        </td>

        {{-- @if (config('system_settings.show_item_conditions'))
          <td>
            <div class="form-group">
              {!! Form::select('conditions[' . $loop->index . ']', ['New' => trans('app.new'), 'Used' => trans('app.used'), 'Refurbished' => trans('app.refurbished')], null, ['class' => 'form-control condition', 'required']) !!}
            </div>
          </td>
        @endif

        <td>
          <div class="form-group">
            <div class="input-group">
              <span class="input-group-addon">{{ config('system_settings.currency.symbol', '$') }}</span>
              {!! Form::number('purchase_prices[' . $loop->index . ']', null, ['class' => 'form-control variant-purchase-price', 'step' => 'any', 'placeholder' => trans('app.placeholder.purchase_price')]) !!}
            </div>
          </div>
        </td>
 --}}
        <td>
          <div class="form-group">
            @include('admin.partials._qty_stepper', [
              'name' => 'sale_prices[' . $loop->index . ']',
              'value' => null,
              'class' => 'form-control variant-price',
              'min' => 0,
              'step' => '0.01',
              'required' => true,
              'placeholder' => trans('app.placeholder.sale_price'),
              'prefix' => get_currency_prefix() ?: config('system_settings.currency.symbol', '$'),
            ])
          </div>
        </td>

        <td>
          <div class="form-group text-muted">
            <i class="fa fa-close deleteThisRow" data-toggle="tooltip" data-placement="left" title="{{ trans('help.delete_this_combination') }}"></i>
          </div>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
