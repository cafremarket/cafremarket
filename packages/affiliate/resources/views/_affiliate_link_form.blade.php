<div class="form-group col-md-12">
  {!! Form::label('slug', trans('packages.affiliate.short_link') . '*', ['class' => 'with-help']) !!}
  {!! Form::text('slug', null, ['class' => 'form-control', 'placeholder' => trans('packages.affiliate.slug'), 'required']) !!}
  <div class="help-block with-errors">
    @isset($link)
      <span class="text-warning"><i class="fa fa-warning"></i> {{ trans('packages.affiliate.slug_edit_warning') }}</span>
    @endisset
  </div>
</div>
