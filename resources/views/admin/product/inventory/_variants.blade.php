@php
  // Show every SKU that belongs to this product, including the default/parent
  // variant — it has no other editable UI now that the General tab is hidden
  // for variable products, so excluding it here made it unmanageable.
  $allVariants = $product->inventories->sortBy(fn ($v) => $v->parent_id === null ? 0 : 1)->values();
@endphp
<table class="table table-default table-variants-editor" id="variantsTable">
  @foreach ($allVariants as $variant)
    @if ($loop->first)
      <thead>
        <tr>
          <th>{{ trans('app.sl_number') }}</th>
          <th>{{ trans('app.form.image') }}</th>
          <th>{{ trans('app.variant') }}</th>
          <th>{{ trans('app.form.sku') }}</th>
          <th>{{ trans('app.form.stock_quantity') }}</th>
          <th>{{ trans('app.form.sale_price') }}</th>
          <th>{{ trans('app.offer_pricing') }}</th>
          <th></th>
        </tr>
      </thead>

      <tbody>
    @endif

    @php
      $hasOffer = ! empty($variant->offer_price);
    @endphp

    <tr class="variant-row">
      <td>{{ $loop->iteration }}</td>

      <td>
        <img src="{{ $variant->image ? get_storage_file_url(optional($variant->image)->path, 'mini') : url('images/placeholders/no_img.png') }}"
             class="variant-summary-thumb" alt="{{ $variant->title }}">
      </td>

      <td>
        <span class="variant-attrs-label">
          @foreach ($variant->attributeValues as $attrVal)
            <span class="label label-primary">{{ $attrVal->value }}</span>
          @endforeach
        </span>
        @if (is_null($variant->parent_id))
          <span class="label label-default" title="{{ trans('help.default_variant_selection') }}">{{ trans('app.is_default') }}</span>
        @endif
      </td>

      <td><span class="variant-summary-sku">{{ $variant->sku }}</span></td>
      <td><span class="variant-summary-qty">{{ $variant->stock_quantity }}</span></td>
      <td><span class="variant-summary-price">{{ get_currency_prefix() }}{{ number_format((float) $variant->sale_price, 2) }}</span></td>

      <td>
        <span class="variant-summary-offer {{ $hasOffer ? '' : 'hide' }}">
          {{ get_currency_prefix() }}{{ number_format((float) $variant->offer_price, 2) }}
        </span>
        <span class="text-muted variant-summary-offer-empty {{ $hasOffer ? 'hide' : '' }}">&mdash;</span>
      </td>

      <td class="text-nowrap">
        {{ Form::hidden('variant_ids[' . $variant->id . ']', $variant->id) }}

        <button type="button" class="btn btn-xs btn-default manageVariantBtn" data-toggle="modal" data-target="#variantManageModal">
          <i class="fa fa-cog"></i> {{ trans('app.manage') }}
        </button>
        @unless (is_null($variant->parent_id))
          <i class="fa fa-close deleteThisRow text-muted" data-toggle="tooltip" data-placement="top" title="{{ trans('help.delete_this_combination') }}"></i>
        @endunless

        @include('admin.product.inventory._variant_fields', [
          'imageName' => 'variant_images[' . $variant->id . ']',
          'imageUrl' => $variant->image ? get_storage_file_url(optional($variant->image)->path, 'mini') : null,
          'skuName' => 'variant_skus[' . $variant->id . ']',
          'skuValue' => $variant->sku,
          'qtyName' => 'variant_quantities[' . $variant->id . ']',
          'qtyValue' => $variant->stock_quantity,
          'priceName' => 'variant_prices[' . $variant->id . ']',
          'priceValue' => number_format((float) $variant->sale_price, 2, '.', ''),
          'offerPriceName' => 'variant_offer_prices[' . $variant->id . ']',
          'offerPriceValue' => $variant->offer_price,
        ])
      </td>
    </tr>

    @if ($loop->last)
      </tbody>
    @endif
  @endforeach
</table>

<p>
  <a href="{{ route('admin.stock.product.addVariant', $product) }}" class="btn btn-default">
    <i class="fa fa-plus"></i> {{ trans('app.add_new_variant') }}
  </a>
</p>
