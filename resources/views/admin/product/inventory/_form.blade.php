@php
  $title_classes = isset($product) ? 'form-control wc-product-title' : 'form-control wc-product-title makeSlug';
  $hasVariants = isset($product) && $product->inventories->whereNotNull('parent_id')->count() > 0;
  $productType = $hasVariants ? 'variable' : 'simple';
@endphp

{{-- WooCommerce-style unified Product + Inventory editor --}}
<div class="wc-product-editor">
  <div class="wc-product-editor__header">
    <h2 class="wc-product-editor__heading">
      {{ isset($product) ? trans('app.update_product') : trans('app.add_product') }}
    </h2>
    <p class="wc-product-editor__sub">{{ trans('help.woo_product_editor_intro') }}</p>
  </div>

  {{-- Product title (like WP title) --}}
  <div class="wc-panel wc-panel--title">
    <div class="form-group mb-0">
      {!! Form::text('name', null, [
        'class' => $title_classes,
        'placeholder' => trans('app.placeholder.product_name_woo'),
        'required',
        'autocomplete' => 'off',
      ]) !!}
      <div class="help-block with-errors"></div>
    </div>
    <div class="wc-permalink">
      <span class="text-muted">{{ rtrim(get_shop_url(Auth::user()->shop ?? optional($product ?? null)->shop), '/') }}/</span>
      {!! Form::text('slug', isset($inventory) ? $inventory->slug : null, [
        'class' => 'form-control input-sm slug wc-permalink__input',
        'placeholder' => trans('app.placeholder.slug'),
        'required',
      ]) !!}
    </div>
  </div>

  <div class="wc-product-editor__layout">
    {{-- ================= MAIN COLUMN ================= --}}
    <div class="wc-product-editor__main">
      {{-- Description --}}
      <div class="wc-panel">
        <div class="wc-panel__title">{{ trans('app.form.description') }}</div>
        <div class="wc-panel__body">
          {!! Form::textarea('description', isset($inventory) ? $inventory->description : null, [
            'class' => 'form-control summernote',
            'rows' => '6',
            'placeholder' => trans('app.placeholder.description'),
            'required',
          ]) !!}
          <div class="help-block with-errors">{!! $errors->first('description', ':message') !!}</div>
        </div>
      </div>

      {{-- Product data (WooCommerce-style vertical tabs) --}}
      <div class="wc-panel wc-product-data">
        <div class="wc-panel__title wc-product-data__header">
          <span>{{ trans('app.product_data') }}</span>
          <div class="wc-product-type">
            <label class="sr-only" for="wc_product_type">{{ trans('app.product_type') }}</label>
            <select id="wc_product_type" class="form-control input-sm">
              <option value="simple" {{ $productType === 'simple' ? 'selected' : '' }}>{{ trans('app.simple_product') }}</option>
              <option value="variable" {{ $productType === 'variable' ? 'selected' : '' }}>{{ trans('app.variable_product') }}</option>
            </select>
          </div>
        </div>

        <div class="wc-product-data__wrap">
          <ul class="wc-product-data__tabs" role="tablist">
            <li class="active">
              <a href="#wc_tab_general" data-toggle="tab"><i class="fa fa-money"></i> {{ trans('app.general') }}</a>
            </li>
            <li>
              <a href="#wc_tab_inventory" data-toggle="tab"><i class="fa fa-archive"></i> {{ trans('app.inventory') }}</a>
            </li>
            <li class="wc-tab-shipping">
              <a href="#wc_tab_shipping" data-toggle="tab"><i class="fa fa-truck"></i> {{ trans('app.shipping') }}</a>
            </li>
            <li class="wc-tab-attributes {{ $productType === 'simple' ? 'hide' : '' }}">
              <a href="#wc_tab_attributes" data-toggle="tab"><i class="fa fa-tags"></i> {{ trans('app.attributes') }}</a>
            </li>
            <li>
              <a href="#wc_tab_advanced" data-toggle="tab"><i class="fa fa-cog"></i> {{ trans('app.advanced') }}</a>
            </li>
          </ul>

          <div class="tab-content wc-product-data__panels">
            {{-- GENERAL: pricing --}}
            <div class="tab-pane active" id="wc_tab_general">
              <div class="wc-option-group">
                <p class="wc-hint wc-simple-only">{{ trans('help.woo_simple_pricing') }}</p>
                <p class="wc-hint wc-variable-only hide">{{ trans('help.woo_variable_pricing') }}</p>

                <div class="form-group">
                  {!! Form::label('sale_price', trans('app.form.sale_price') . ' (Regular)') !!}
                  <div class="input-group">
                    @if (get_currency_prefix())
                      <span class="input-group-addon">{{ get_currency_prefix() }}</span>
                    @endif
                    {!! Form::number('sale_price', isset($inventory) ? $inventory->sale_price : null, [
                      'class' => 'form-control',
                      'step' => 'any',
                      'placeholder' => '0.00',
                    ]) !!}
                    @if (get_currency_suffix())
                      <span class="input-group-addon">{{ get_currency_suffix() }}</span>
                    @endif
                  </div>
                </div>

                <div class="form-group">
                  {!! Form::label('offer_price', trans('app.form.offer_price') . ' (Sale)') !!}
                  <div class="input-group">
                    @if (get_currency_prefix())
                      <span class="input-group-addon">{{ get_currency_prefix() }}</span>
                    @endif
                    {!! Form::number('offer_price', isset($inventory) ? $inventory->offer_price : null, [
                      'class' => 'form-control',
                      'step' => 'any',
                      'placeholder' => '0.00',
                    ]) !!}
                    @if (get_currency_suffix())
                      <span class="input-group-addon">{{ get_currency_suffix() }}</span>
                    @endif
                  </div>
                </div>

                <div class="row">
                  <div class="col-sm-6">
                    <div class="form-group">
                      {!! Form::label('offer_start', trans('app.form.offer_start')) !!}
                      {!! Form::text('offer_start', isset($inventory) ? $inventory->offer_start : null, [
                        'class' => 'form-control datetimepicker',
                        'placeholder' => trans('app.placeholder.offer_start'),
                      ]) !!}
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      {!! Form::label('offer_end', trans('app.form.offer_end')) !!}
                      {!! Form::text('offer_end', isset($inventory) ? $inventory->offer_end : null, [
                        'class' => 'form-control datetimepicker',
                        'placeholder' => trans('app.placeholder.offer_end'),
                      ]) !!}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- INVENTORY --}}
            <div class="tab-pane" id="wc_tab_inventory">
              <div class="wc-option-group">
                <div class="form-group">
                  {!! Form::label('sku', trans('app.form.sku')) !!}
                  {!! Form::text('sku', isset($inventory) ? $inventory->sku : null, [
                    'class' => 'form-control',
                    'placeholder' => trans('app.placeholder.sku'),
                  ]) !!}
                </div>

                <div class="row">
                  <div class="col-sm-6">
                    <div class="form-group">
                      {!! Form::label('stock_quantity', trans('app.form.stock_quantity')) !!}
                      {!! Form::number('stock_quantity', isset($inventory) ? $inventory->stock_quantity : 1, [
                        'min' => 0,
                        'class' => 'form-control',
                      ]) !!}
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      {!! Form::label('min_order_quantity', trans('app.form.min_order_quantity')) !!}
                      {!! Form::number('min_order_quantity', isset($inventory) ? $inventory->min_order_quantity : 1, [
                        'min' => 1,
                        'class' => 'form-control',
                      ]) !!}
                    </div>
                  </div>
                </div>

                @if (config('system_settings.show_item_conditions'))
                  <div class="form-group">
                    {!! Form::label('condition', trans('app.form.condition')) !!}
                    {!! Form::select('condition', [
                      'New' => trans('app.new'),
                      'Used' => trans('app.used'),
                      'Refurbished' => trans('app.refurbished'),
                    ], isset($inventory) ? $inventory->condition : 'New', [
                      'class' => 'form-control select2-normal',
                    ]) !!}
                  </div>
                  <div class="form-group">
                    {!! Form::label('condition_note', trans('app.form.condition_note')) !!}
                    {!! Form::text('condition_note', isset($inventory) ? $inventory->condition_note : null, ['class' => 'form-control']) !!}
                  </div>
                @else
                  {!! Form::hidden('condition', 'New') !!}
                @endif

                <div class="form-group">
                  {!! Form::label('warehouse_id', trans('app.form.warehouse')) !!}
                  {!! Form::select('warehouse_id', $warehouses, isset($warehouse) ? $warehouse->id : config('shop_settings.default_warehouse_id'), [
                    'class' => 'form-control select2',
                    'placeholder' => trans('app.placeholder.select'),
                  ]) !!}
                </div>
              </div>
            </div>

            {{-- SHIPPING --}}
            <div class="tab-pane" id="wc_tab_shipping">
              <div class="wc-option-group">
                <label class="admin-catalog-rule" style="margin-bottom:12px;">
                  {{ Form::hidden('requires_shipping', 0) }}
                  {!! Form::checkbox('requires_shipping', 1, !isset($product) ? 1 : null, [
                    'id' => 'requires_shipping',
                    'class' => 'admin-catalog-rule__input requires_shipping',
                  ]) !!}
                  <span class="admin-catalog-rule__meta">
                    <span class="admin-catalog-rule__title">{{ trans('app.form.requires_shipping') }}</span>
                  </span>
                </label>

                <div id="form_shipping_section" class="form_shipping_section">
                  <div class="form-group">
                    {!! Form::label('shipping_type', 'Shipping charge') !!}
                    {!! Form::select('shipping_type', [
                      'inherit' => 'Use shop default',
                      'free' => trans('theme.free_shipping') ?: 'Free shipping',
                      'fixed' => 'Fixed charge',
                      'km' => 'Per kilometre',
                    ], isset($inventory) ? ($inventory->shipping_type ?: 'inherit') : 'inherit', ['class' => 'form-control', 'id' => 'item_shipping_type']) !!}
                  </div>

                  <div class="form-group item-ship-fixed">
                    {!! Form::label('shipping_fixed_rate', 'Fixed rate') !!}
                    {!! Form::number('shipping_fixed_rate', isset($inventory) ? $inventory->shipping_fixed_rate : null, ['class' => 'form-control', 'step' => 'any', 'min' => 0]) !!}
                  </div>

                  <div class="form-group item-ship-km">
                    {!! Form::label('shipping_base_fee', 'KM base fee') !!}
                    {!! Form::number('shipping_base_fee', isset($inventory) ? $inventory->shipping_base_fee : null, ['class' => 'form-control', 'step' => 'any', 'min' => 0]) !!}
                  </div>

                  <div class="form-group item-ship-km">
                    {!! Form::label('shipping_per_km_rate', 'Rate per KM') !!}
                    {!! Form::number('shipping_per_km_rate', isset($inventory) ? $inventory->shipping_per_km_rate : null, ['class' => 'form-control', 'step' => 'any', 'min' => 0]) !!}
                  </div>

                  {{ Form::hidden('free_shipping', 0) }}
                  {!! Form::checkbox('free_shipping', 1, null, ['id' => 'free_shipping', 'style' => 'display:none']) !!}
                  <script>
                    (function () {
                      function syncItemShip() {
                        var t = document.getElementById('item_shipping_type');
                        if (!t) return;
                        var v = t.value;
                        document.querySelectorAll('.item-ship-fixed').forEach(function (el) {
                          el.style.display = v === 'fixed' ? '' : 'none';
                        });
                        document.querySelectorAll('.item-ship-km').forEach(function (el) {
                          el.style.display = v === 'km' ? '' : 'none';
                        });
                        var free = document.getElementById('free_shipping');
                        if (free) free.checked = v === 'free';
                      }
                      document.addEventListener('DOMContentLoaded', function () {
                        var t = document.getElementById('item_shipping_type');
                        if (t) { t.addEventListener('change', syncItemShip); syncItemShip(); }
                      });
                    })();
                  </script>

                  <div class="form-group">
                    {!! Form::label('shipping_weight', trans('app.form.shipping_weight')) !!}
                    <div class="input-group">
                      {!! Form::number('shipping_weight', isset($inventory) ? $inventory->shipping_weight : null, [
                        'class' => 'form-control',
                        'step' => 'any',
                        'min' => 0,
                      ]) !!}
                      <span class="input-group-addon">{{ config('system_settings.weight_unit') ?? 'gm' }}</span>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-sm-4">
                      <div class="form-group">
                        {!! Form::label('length', trans('app.form.length')) !!}
                        {!! Form::text('length', null, ['class' => 'form-control']) !!}
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div class="form-group">
                        {!! Form::label('width', trans('app.form.width')) !!}
                        {!! Form::text('width', null, ['class' => 'form-control']) !!}
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div class="form-group">
                        {!! Form::label('height', trans('app.form.height')) !!}
                        {!! Form::text('height', null, ['class' => 'form-control']) !!}
                      </div>
                    </div>
                  </div>

                  {!! Form::hidden('distance_unit', 'cm') !!}

                  @if (is_incevio_package_loaded('packaging'))
                    <div class="form-group">
                      {!! Form::label('packaging_list[]', trans('packaging::lang.packagings')) !!}
                      {!! Form::select('packaging_list[]', $packagings, isset($inventory) ? null : config('shop_settings.default_packaging_ids'), [
                        'class' => 'form-control select2-normal',
                        'multiple' => 'multiple',
                      ]) !!}
                    </div>
                  @endif
                </div>

                <hr>
                <label class="admin-catalog-rule">
                  {{ Form::hidden('downloadable', 0) }}
                  {!! Form::checkbox('downloadable', 1, null, [
                    'id' => 'downloadable',
                    'class' => 'admin-catalog-rule__input downloadable',
                  ]) !!}
                  <span class="admin-catalog-rule__meta">
                    <span class="admin-catalog-rule__title">{{ trans('app.form.downloadable') }}</span>
                  </span>
                </label>

                <fieldset id="downloadable_section" class="mt-3">
                  @if (isset($inventory))
                    <ul class="mailbox-attachments clearfix">
                      @foreach ($inventory->attachments as $attachment)
                        <li>
                          <div class="mailbox-attachment-info">
                            <a href="{{ route('attachment.download', $attachment) }}" class="mailbox-attachment-name">
                              <i class="fa fa-file"></i> {{ $attachment->name }}
                            </a>
                          </div>
                        </li>
                      @endforeach
                    </ul>
                  @endif
                  <div class="form-group">
                    {!! Form::label('digital_file', trans('app.form.digital_file')) !!}
                    <input type="file" name="digital_file" id="digital_file" class="form-control" />
                  </div>
                  <div class="form-group">
                    {!! Form::label('download_limit', trans('app.form.download_limit')) !!}
                    {!! Form::number('download_limit', isset($inventory) ? $inventory->download_limit : null, ['class' => 'form-control']) !!}
                  </div>
                </fieldset>
              </div>
            </div>

            {{-- ATTRIBUTES / VARIATIONS --}}
            <div class="tab-pane" id="wc_tab_attributes">
              <div class="wc-option-group">
                @include('admin.product.inventory._attributes_tab')
              </div>
            </div>

            {{-- ADVANCED --}}
            <div class="tab-pane" id="wc_tab_advanced">
              <div class="wc-option-group">
                <fieldset>
                  <legend>{{ trans('app.form.key_features') }}
                    <button type="button" id="AddMoreField" class="btn btn-xs btn-new"><i class="fa fa-plus"></i></button>
                  </legend>
                  <div id="DynamicInputsWrapper">
                    @if (isset($inventory) && $inventory->key_features)
                      @foreach (unserialize($inventory->key_features) as $key_feature)
                        <div class="form-group">
                          <div class="input-group">
                            {!! Form::text('key_features[]', $key_feature, ['class' => 'form-control input-sm']) !!}
                            <span class="input-group-addon"><i class="fa fa-times removeThisInputBox"></i></span>
                          </div>
                        </div>
                      @endforeach
                    @else
                      <div class="form-group">
                        <div class="input-group">
                          {!! Form::text('key_features[]', null, ['id' => 'field_1', 'class' => 'form-control input-sm', 'placeholder' => trans('app.placeholder.key_feature')]) !!}
                          <span class="input-group-addon"><i class="fa fa-times removeThisInputBox"></i></span>
                        </div>
                      </div>
                    @endif
                  </div>
                </fieldset>

                <div class="row">
                  <div class="col-sm-4">
                    <div class="form-group">
                      {!! Form::label('mpn', trans('app.form.mpn')) !!}
                      {!! Form::text('mpn', null, ['class' => 'form-control']) !!}
                    </div>
                  </div>
                  <div class="col-sm-4">
                    <div class="form-group">
                      {!! Form::label('gtin', trans('app.form.gtin')) !!}
                      {!! Form::text('gtin', null, ['class' => 'form-control']) !!}
                    </div>
                  </div>
                  <div class="col-sm-4">
                    <div class="form-group">
                      {!! Form::label('gtin_type', trans('app.form.gtin_type')) !!}
                      {!! Form::select('gtin_type', $gtin_types, null, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.gtin_type')]) !!}
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  {!! Form::label('linked_items[]', trans('app.form.linked_items')) !!}
                  {!! Form::select('linked_items[]', $inventories, isset($inventory) ? unserialize($inventory->linked_items) : null, [
                    'class' => 'form-control select2-normal',
                    'multiple' => 'multiple',
                  ]) !!}
                </div>

                <div class="form-group">
                  {!! Form::label('purchase_price', trans('app.form.purchase_price')) !!}
                  <div class="input-group">
                    @if (get_currency_prefix())
                      <span class="input-group-addon">{{ get_currency_prefix() }}</span>
                    @endif
                    {!! Form::number('purchase_price', isset($inventory) ? $inventory->purchase_price : null, ['class' => 'form-control', 'step' => 'any']) !!}
                  </div>
                </div>

                <div class="form-group">
                  {!! Form::label('supplier_id', trans('app.form.supplier')) !!}
                  {!! Form::select('supplier_id', $suppliers, isset($inventory) ? null : config('shop_settings.default_supplier_id'), [
                    'class' => 'form-control select2',
                    'placeholder' => trans('app.placeholder.select'),
                  ]) !!}
                </div>

                <div class="form-group">
                  {!! Form::label('meta_title', trans('app.form.meta_title')) !!}
                  {!! Form::text('meta_title', isset($inventory) ? $inventory->meta_title : null, ['class' => 'form-control']) !!}
                </div>
                <div class="form-group">
                  {!! Form::label('meta_description', trans('app.form.meta_description')) !!}
                  {!! Form::text('meta_description', isset($inventory) ? $inventory->meta_description : null, [
                    'class' => 'form-control',
                    'maxlength' => config('seo.meta.description_character_limit', '160'),
                  ]) !!}
                </div>

                @if (is_incevio_package_loaded('pharmacy'))
                  @include('pharmacy::inventory_form')
                @endif
                @if (is_incevio_package_loaded('wholesale'))
                  @include('wholesale::wholesale_inventory_form')
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Gallery --}}
      <div class="wc-panel">
        <div class="wc-panel__title">{{ trans('app.form.images') }} ({{ trans('app.product_gallery') }})</div>
        <div class="wc-panel__body">
          <div class="file-loading">
            <input id="dropzone-input" name="images[]" type="file" accept="image/*" multiple>
          </div>
          <span class="small text-muted">
            <i class="fa fa-info-circle"></i>
            {{ trans('help.multi_img_upload_instruction', ['size' => getAllowedMaxImgSize(), 'number' => getMaxNumberOfImgsForInventory(), 'dimension' => '800 x 800']) }}
          </span>
        </div>
      </div>
    </div>

    {{-- ================= SIDEBAR ================= --}}
    <div class="wc-product-editor__side">
      {{-- Publish --}}
      <div class="wc-panel">
        <div class="wc-panel__title">{{ trans('app.publish') }}</div>
        <div class="wc-panel__body">
          <div class="form-group">
            {!! Form::label('active', trans('app.form.status') . '*') !!}
            {!! Form::select('active', ['1' => trans('app.active'), '0' => trans('app.inactive')], isset($inventory) ? $inventory->active : 1, [
              'class' => 'form-control select2-normal',
              'required',
            ]) !!}
          </div>
          <div class="form-group">
            {!! Form::label('available_from', trans('app.form.available_from')) !!}
            {!! Form::text('available_from', isset($inventory) ? $inventory->available_from : null, [
              'class' => 'datetimepicker form-control',
              'placeholder' => trans('app.placeholder.available_from'),
            ]) !!}
          </div>
          <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fa fa-check"></i>
            {{ isset($product) ? trans('app.form.update') : trans('app.publish') }}
          </button>
          <p class="help-block text-center">* {{ trans('app.form.required_fields') }}</p>
        </div>
      </div>

      {{-- Product image --}}
      <div class="wc-panel">
        <div class="wc-panel__title">{{ trans('app.featured_image') }}</div>
        <div class="wc-panel__body">
          @if (isset($product) && $product->featureImage)
            <img src="{{ get_storage_file_url($product->featureImage->path, 'small') }}" alt="" class="img-responsive" style="margin-bottom:8px;">
            <label>
              {!! Form::checkbox('delete_image[feature]', 1, null, ['class' => 'icheck']) !!}
              {{ trans('app.form.delete_image') }}
            </label>
          @endif
          <div class="fileUpload btn btn-default btn-block btn-flat">
            <span>{{ trans('app.form.upload') }}</span>
            <input type="file" name="images[feature]" id="uploadBtn" class="upload" />
          </div>
          <input id="uploadFile" placeholder="{{ trans('app.featured_image') }}" class="form-control" disabled="disabled" style="margin-top:8px; height:28px;" />
        </div>
      </div>

      {{-- Categories --}}
      <div class="wc-panel">
        <div class="wc-panel__title">{{ trans('app.form.categories') }} *</div>
        <div class="wc-panel__body">
          {!! Form::select('category_list[]', $categories, null, [
            'class' => 'form-control select2-normal',
            'multiple' => 'multiple',
            'required',
          ]) !!}
          <div class="help-block with-errors"></div>
        </div>
      </div>

      {{-- Branding / tags --}}
      <div class="wc-panel">
        <div class="wc-panel__title">{{ trans('app.branding') }}</div>
        <div class="wc-panel__body">
          <div class="form-group">
            {!! Form::label('brand', trans('app.form.brand')) !!}
            {!! Form::text('brand', isset($inventory) ? $inventory->brand : null, ['class' => 'form-control']) !!}
          </div>
          <div class="form-group">
            {!! Form::label('manufacturer_id', trans('app.form.manufacturer')) !!}
            {!! Form::select('manufacturer_id', $manufacturers, null, [
              'class' => 'form-control select2',
              'placeholder' => trans('app.placeholder.manufacturer'),
            ]) !!}
          </div>
          <div class="form-group">
            {!! Form::label('model_number', trans('app.form.model_number')) !!}
            {!! Form::text('model_number', null, ['class' => 'form-control']) !!}
          </div>
          <div class="form-group">
            {!! Form::label('origin_country', trans('app.form.origin')) !!}
            {!! Form::select('origin_country', $countries, null, [
              'class' => 'form-control select2',
              'placeholder' => trans('app.placeholder.origin'),
            ]) !!}
          </div>
          <div class="form-group mb-0">
            {!! Form::label('tag_list[]', trans('app.form.tags')) !!}
            {!! Form::select('tag_list[]', $tags, null, ['class' => 'form-control select2-tag', 'multiple' => 'multiple']) !!}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
