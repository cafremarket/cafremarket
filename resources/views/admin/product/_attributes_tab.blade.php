{{-- Catalog product Attributes tab (select attributes; variants managed when listing has inventory) --}}
@php
  $selectedProductAttrs = isset($product)
    ? $product->categories->pluck('attrsList')->flatten()->pluck('id')->unique()->values()->all()
    : [];
  $variantInventories = isset($product)
    ? $product->inventories->whereNotNull('parent_id')
    : collect();
@endphp

<div class="product-attributes-tab">
  <div class="alert alert-info">
    <i class="fa fa-info-circle"></i>
    {{ trans('help.catalog_attributes_tab_intro') }}
    <a href="{{ mp_url('merchant/catalog/attribute') }}" target="_blank">{{ trans('app.manage_attributes') }}</a>
  </div>

  <div class="form-group">
    {!! Form::label('attrsList[]', trans('app.form.product_attributes'), ['class' => 'with-help']) !!}
    <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.product_attributes') }}"></i>
    {!! Form::select('attrsList[]', $attrsList ?? [], $selectedProductAttrs, [
      'class' => 'form-control select2-normal',
      'multiple' => 'multiple',
    ]) !!}
    <div class="help-block">{{ trans('help.product_attributes_manage') }}</div>
  </div>

  @if ($variantInventories->count())
    <fieldset>
      <legend>{{ trans('app.variants') }} — {{ trans('help.each_variant_own_inventory') }}</legend>
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>{{ trans('app.form.variants') }}</th>
              <th>{{ trans('app.form.sku') }}</th>
              <th>{{ trans('app.form.stock_quantity') }}</th>
              <th>{{ trans('app.form.sale_price') }}</th>
              <th>{{ trans('app.form.image') }}</th>
              <th>{{ trans('app.option') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($variantInventories as $variant)
              <tr>
                <td>
                  @foreach ($variant->attributeValues as $attrVal)
                    <span class="label label-default">{{ $attrVal->value }}</span>
                  @endforeach
                </td>
                <td><code>{{ $variant->sku }}</code></td>
                <td>{{ $variant->stock_quantity }}</td>
                <td>{{ get_formated_currency($variant->sale_price) }}</td>
                <td>
                  @if ($variant->image)
                    <img src="{{ get_storage_file_url(optional($variant->image)->path, 'tiny') }}" alt="{{ $variant->sku }}" class="img-sm">
                  @else
                    —
                  @endif
                </td>
                <td>
                  <a href="{{ mp_route('admin.stock.product.edit', $product->id) }}" class="btn btn-xs btn-default">
                    <i class="fa fa-edit"></i> {{ trans('app.edit') }}
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <p class="help-block">
        {{ trans('help.edit_variants_via_inventory') }}
        @if (isset($product))
          <a href="{{ mp_route('admin.stock.product.edit', $product->id) }}">{{ trans('app.edit_with_inventory') }}</a>
        @endif
      </p>
    </fieldset>
  @else
    <div class="well well-sm text-muted">
      <i class="fa fa-cubes"></i>
      {{ trans('help.no_variants_yet_use_inventory_product') }}
    </div>
  @endif
</div>
