<div class="form-group">
  {!! Form::label('name', trans('app.form.attribute_name') . '*') !!}
  <div class="input-group">
    {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.attribute_name'), 'required']) !!}
    <span class="input-group-addon" id="basic-addon1">
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.attribute_name') }}"></i>
    </span>
  </div>
  <div class="help-block with-errors"></div>
</div>

<div class="form-group">
  {!! Form::label('categories[]', trans('app.form.categories')) !!}
  {!! Form::select('categories[]', $categories, $selectedCategories ?? null, ['class' => 'form-control select2-normal', 'multiple' => 'multiple']) !!}
  <div class="help-block with-errors">{{ trans('help.category_attributes') }}</div>
</div>

<p class="help-block text-muted">
  {{ trans('help.attribute_presets_hint') ?? 'Tip: use presets like Colour, Size, Material. After saving, add option values (e.g. Red, M, Cotton) from the Entities screen.' }}
</p>

<p class="help-block">* {{ trans('app.form.required_fields') }}</p>
