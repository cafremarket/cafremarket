@php
  $catalogTree = $catalogTree ?? [];
  $parentCategories = $parentCategories ?? [];
  $selectedParentId = old('parent_category_id');
  $selectedSubIds = old('category_list', []);

  if (! is_array($selectedSubIds)) {
    $selectedSubIds = $selectedSubIds ? [$selectedSubIds] : [];
  }

  if (! $selectedParentId && isset($product) && $product->relationLoaded('subCategories') === false) {
    $product->load('subCategories');
  }

  if (! $selectedParentId && isset($product) && $product->subCategories && $product->subCategories->count()) {
    $selectedParentId = $product->subCategories->first()->category_id;
    if (empty($selectedSubIds)) {
      $selectedSubIds = $product->subCategories->pluck('id')->all();
    }
  }

  $subcategoryOptions = [];
  if ($selectedParentId && isset($catalogTree[$selectedParentId]['subcategories'])) {
    $subcategoryOptions = $catalogTree[$selectedParentId]['subcategories'];
  }
@endphp

<div class="form-group">
  {!! Form::label('parent_category_id', trans('app.form.category') . '*') !!}
  {!! Form::select('parent_category_id', $parentCategories, $selectedParentId, [
    'class' => 'form-control select2-normal js-parent-category',
    'placeholder' => trans('app.placeholder.category'),
    'required',
    'id' => 'parent_category_id',
  ]) !!}
  <div class="help-block with-errors">{!! $errors->first('parent_category_id', ':message') !!}</div>
</div>

<div class="form-group">
  {!! Form::label('category_list[]', trans('app.subcategory') . '*') !!}
  {!! Form::select('category_list[]', $subcategoryOptions, $selectedSubIds, [
    'class' => 'form-control select2-normal js-subcategory-list',
    'multiple' => 'multiple',
    'required',
    'id' => 'category_list',
    'data-placeholder' => trans('app.placeholder.category'),
  ]) !!}
  <div class="help-block with-errors">{!! $errors->first('category_list', ':message') !!}</div>
  <div class="help-block text-muted">
    <i class="fa fa-tags"></i> {{ trans('help.attributes_on_next_tab') }}
  </div>
</div>

<script type="application/json" id="catalog-tree-json">{!! json_encode($catalogTree, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
