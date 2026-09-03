<div class="form-group">
  {!! Form::label('name', trans('app.form.name') . '*', ['class' => 'with-help']) !!}
  {!! Form::text('name', null, ['class' => 'form-control makeSlug', 'placeholder' => trans('app.placeholder.manufacturer_name'), 'required']) !!}
  <div class="help-block with-errors"></div>
</div>

{!! Form::hidden('slug', null, ['class' => 'slug']) !!}
{!! Form::hidden('active', isset($manufacturer) ? $manufacturer->active : 1) !!}

<div class="form-group">
  {!! Form::label('country_id', trans('app.form.country')) !!}
  {!! Form::select('country_id', $countries, null, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.country')]) !!}
</div>

<div class="form-group">
  {!! Form::label('images[logo]', 'Brand Logo', ['class' => 'with-help']) !!}
  @if (isset($manufacturer) && $manufacturer->logoImage)
    <label>
      <img src="{{ get_logo_url($manufacturer, 'small') }}" alt="{{ trans('app.logo') }}">
      <span style="margin-left: 10px;">
        {!! Form::checkbox('delete_image[logo]', 1, null, ['class' => 'icheck']) !!} {{ trans('app.form.delete_logo') }}
      </span>
    </label>
  @endif
  <div class="row">
    <div class="col-md-9 nopadding-right">
      <input id="uploadFile" placeholder="{{ trans('app.placeholder.logo') }}" class="form-control" disabled="disabled" style="height: 28px;" />
      <div class="help-block with-errors">{{ trans('help.logo_img_size') }}</div>
    </div>
    <div class="col-md-3 nopadding-left">
      <div class="fileUpload btn btn-primary btn-block btn-flat">
        <span>{{ trans('app.form.upload') }}</span>
        <input type="file" name="images[logo]" id="uploadBtn" class="upload" />
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-4 nopadding-right">
    <div class="form-group">
      {!! Form::label('url', (trans('app.form.website') ?? 'Website') . ' ' . trans('app.form.optional')) !!}
      {!! Form::text('url', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.url')]) !!}
    </div>
  </div>
  <div class="col-md-4 nopadding-right nopadding-left">
    <div class="form-group">
      {!! Form::label('email', trans('app.form.email_address') . ' ' . trans('app.form.optional')) !!}
      {!! Form::email('email', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.valid_email')]) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
  <div class="col-md-4 nopadding-left">
    <div class="form-group">
      {!! Form::label('phone', trans('app.form.phone') . ' ' . trans('app.form.optional')) !!}
      {!! Form::text('phone', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.phone_number')]) !!}
    </div>
  </div>
</div>

<p class="help-block">* {{ trans('app.form.required_fields') }}</p>
