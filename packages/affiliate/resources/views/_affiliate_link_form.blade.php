<div class="form-group col-md-12">
  {!! Form::label('slug', trans('app.slug') . '*', ['class' => 'with-help']) !!}
  <div class="input-group">
    <div class="input-group-addon">
      {{ Auth::guard('affiliate')->user()->username }}/
    </div>

    {!! Form::text('slug', isset($link) ? null : $item->slug, ['class' => 'form-control slug', 'placeholder' => trans('packages.affiliate.slug'), 'required']) !!}
  </div>
  <div class="help-block with-errors">
    @isset($link)
      <span class="text-warning"><i class="fa fa-warning"></i> {{ trans('packages.affiliate.slug_edit_warning') }}</span>
    @endisset
  </div>
</div>
