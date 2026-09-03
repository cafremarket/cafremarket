{{-- Attributes & Variations (WooCommerce-style panel content) --}}
@php
  $selectedProductAttrs = isset($product)
    ? $product->categories->pluck('attrsList')->flatten()->pluck('id')->unique()->values()->all()
    : [];
  $hasVariants = isset($product) && $product->inventories->whereNotNull('parent_id')->count() > 0;
@endphp

<div class="product-attributes-tab">
  <p class="wc-hint">
    {{ trans('help.optional_variants_hint') }}
    <a href="{{ mp_url('merchant/catalog/attribute') }}" target="_blank">{{ trans('app.manage_attributes') }}</a>
  </p>

  <div class="row">
    <div class="col-md-8">
      <div class="form-group">
        {!! Form::label('attrsList[]', trans('app.form.product_attributes')) !!}
        {!! Form::select('attrsList[]', $attrsList ?? [], $selectedProductAttrs, [
          'id' => 'product_attrs_list',
          'class' => 'form-control select2-normal',
          'multiple' => 'multiple',
        ]) !!}
      </div>
    </div>
    <div class="col-md-4">
      <div class="form-group">
        <label>&nbsp;</label>
        <button type="button" class="btn btn-default btn-block" id="reloadProductAttributes">
          <i class="fa fa-refresh"></i> {{ trans('app.load_attribute_options') }}
        </button>
      </div>
    </div>
  </div>

  @if ($hasVariants)
    <fieldset>
      <legend>{{ trans('app.variants') }}</legend>
      <p class="help-block text-warning">
        <i class="fa fa-exclamation-triangle"></i> {{ trans('help.variant_pricing_overrides_base') }}
      </p>
      @include('admin.product.inventory._variants')
    </fieldset>
  @else
    <div id="myAttributes" class="py-2 mb-2">
      <div id="attributesFieldset" class="mb-3">
        <p class="text-muted text-center">{{ trans('help.select_category_or_attributes') }}</p>
      </div>

      <div id="set-variant-btn-block" class="text-center hide">
        <p class="mb-3">{{ trans('help.choose_attributes') }}</p>
        <button type="button" class="btn btn-primary" id="setCombinations">
          <i class="fa fa-th-list"></i> {{ trans('app.set_variants') }}
        </button>
      </div>
    </div>

    <div id="combinationsPlaceholder"></div>
  @endif
</div>
