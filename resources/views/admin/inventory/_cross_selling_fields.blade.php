@isset($linkable_items)
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.cross_selling'),
    'icon' => 'fa-link',
    'class' => 'admin-form-section',
  ])
    <div class="form-group">
      {!! Form::label('linked_items[]', trans('app.form.linked_items'), ['class' => 'with-help']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" title="{{ trans('help.inventory_linked_items') }}"></i>
      {!! Form::select('linked_items[]', $linkable_items, isset($inventory) ? unserialize($inventory->linked_items) : null, ['class' => 'form-control select2-normal', 'multiple' => 'multiple']) !!}
      <div class="help-block with-errors"></div>
    </div>
  @include('admin.partials.ui.card_end')
@endisset
