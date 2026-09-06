<legend>{{ trans('app.variants') }}</legend>
<p class="help-block">{{ trans('help.default_variant_selection') }}</p>
<table class="table table-default table-variants-editor" id="variantsTable">
  <thead>
    <tr>
      <th>{{ trans('app.sl_number') }}</th>
      <th>{{ trans('app.is_default') }}</th>
      <th>{{ trans('app.form.image') }}</th>
      <th>{{ trans('app.form.variants') }}</th>
      <th>{{ trans('app.form.sku') }}</th>
      <th>{{ trans('app.form.stock_quantity') }}</th>
      <th>{{ trans('app.form.sale_price') }}</th>
      <th>{{ trans('app.offer_pricing') }}</th>
      <th></th>
    </tr>
  </thead>

  <tbody>
    @foreach ($combinations as $combination)
      <tr class="variant-row">
        <td>{{ $loop->iteration }}</td>

        <td class="text-center">
          <label class="radio-inline" title="{{ trans('help.default_variant_selection') }}">
            <input type="radio" name="default_variant" value="{{ $loop->index }}" {{ $loop->first ? 'checked' : '' }}>
            {{ trans('app.is_default') }}
          </label>
        </td>

        <td>
          <img src="{{ url('images/placeholders/no_img.png') }}" class="variant-summary-thumb" alt="variant-{{ $loop->index }}">
        </td>

        <td>
          <span class="variant-attrs-label">
            @foreach ($combination as $attrId => $attrValue)
              {{ Form::hidden('variants[' . $loop->parent->index . '][' . $attrId . ']', key($attrValue)) }}
              <span class="label label-primary">{{ current($attrValue) }}</span>
            @endforeach
          </span>
        </td>

        <td><span class="variant-summary-sku">&mdash;</span></td>
        <td><span class="variant-summary-qty">&mdash;</span></td>
        <td><span class="variant-summary-price">&mdash;</span></td>

        <td>
          <span class="variant-summary-offer hide"></span>
          <span class="text-muted variant-summary-offer-empty">&mdash;</span>
        </td>

        <td class="text-nowrap">
          <button type="button" class="btn btn-xs btn-default manageVariantBtn" data-toggle="modal" data-target="#variantManageModal">
            <i class="fa fa-cog"></i> {{ trans('app.manage') }}
          </button>
          <i class="fa fa-close deleteThisRow text-muted" data-toggle="tooltip" data-placement="left" title="{{ trans('help.delete_this_combination') }}"></i>

          @include('admin.product.inventory._variant_fields', [
            'imageName' => 'variant_images[' . $loop->index . ']',
            'imageUrl' => null,
            'skuName' => 'skus[' . $loop->index . ']',
            'skuValue' => null,
            'qtyName' => 'stock_quantities[' . $loop->index . ']',
            'qtyValue' => null,
            'priceName' => 'sale_prices[' . $loop->index . ']',
            'priceValue' => null,
            'offerPriceName' => 'offer_prices[' . $loop->index . ']',
            'offerPriceValue' => null,
          ])
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
