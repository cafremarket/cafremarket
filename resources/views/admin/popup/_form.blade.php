<div class="row">
  <div class="col-md-6 nopadding-right">
    <div class="form-group">
      {!! Form::label('title', trans('app.form.title').'*', ['class' => 'with-help']) !!}

      {!! Form::text('title', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.title'), 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>

  <div class="col-md-6 nopadding-left">
    <div class="form-group">
      {!! Form::label('headline', trans('app.form.headline'), ['class' => 'with-help']) !!}

      {!! Form::text('headline', null, ['class' => 'form-control']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="form-group">
      {!! Form::label('description', trans('app.form.description'), ['class' => 'with-help']) !!}

      {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 2]) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6 nopadding-right">
    <div class="form-group">
      {!! Form::label('button_label', trans('app.form.button_label'), ['class' => 'with-help']) !!}

      {!! Form::text('button_label', null, ['class' => 'form-control']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>

  <div class="col-md-6 nopadding-left">
    <div class="form-group">
      {!! Form::label('button_link', trans('app.form.button_link'), ['class' => 'with-help']) !!}

      {!! Form::text('button_link', null, ['class' => 'form-control', 'placeholder' => 'https://...']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
</div>

<hr>

<div class="row">
  <div class="col-md-4 nopadding-right">
    <div class="form-group">
      {!! Form::label('platform', trans('app.platform'), ['class' => 'with-help']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.popup_platform') }}"></i>

      {!! Form::select('platform', [
          \App\Models\Popup::PLATFORM_ALL => trans('app.all'),
          \App\Models\Popup::PLATFORM_WEB => trans('app.website'),
          \App\Models\Popup::PLATFORM_APP => trans('app.mobile_app'),
      ], isset($popup) ? null : \App\Models\Popup::PLATFORM_ALL, ['class' => 'form-control']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>

  <div class="col-md-4 nopadding-right">
    <div class="form-group">
      {!! Form::label('page', trans('app.form.page'), ['class' => 'with-help']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.popup_page') }}"></i>

      {!! Form::select('page', [
          \App\Models\Popup::PAGE_ALL => trans('app.all'),
          \App\Models\Popup::PAGE_HOME => trans('app.popup_page_home'),
          \App\Models\Popup::PAGE_PRODUCT => trans('app.popup_page_product'),
          \App\Models\Popup::PAGE_CATEGORY => trans('app.popup_page_category'),
          \App\Models\Popup::PAGE_CART => trans('app.popup_page_cart'),
          \App\Models\Popup::PAGE_CHECKOUT => trans('app.popup_page_checkout'),
      ], isset($popup) ? null : \App\Models\Popup::PAGE_ALL, ['class' => 'form-control']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>

  <div class="col-md-4 nopadding-left">
    <div class="form-group">
      {!! Form::label('user_type', trans('app.form.user_type'), ['class' => 'with-help']) !!}

      {!! Form::select('user_type', [
          \App\Models\Popup::USER_TYPE_ALL => trans('app.all'),
          \App\Models\Popup::USER_TYPE_GUEST => trans('app.guest'),
          \App\Models\Popup::USER_TYPE_CUSTOMER => trans('app.customer'),
      ], isset($popup) ? null : \App\Models\Popup::USER_TYPE_ALL, ['class' => 'form-control']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-4 nopadding-right">
    <div class="form-group">
      {!! Form::label('frequency', trans('app.form.frequency'), ['class' => 'with-help']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.popup_frequency') }}"></i>

      {!! Form::select('frequency', [
          \App\Models\Popup::FREQUENCY_EVERY_PAGE_LOAD => trans('app.popup_frequency_every_page_load'),
          \App\Models\Popup::FREQUENCY_ONCE_PER_SESSION => trans('app.popup_frequency_once_per_session'),
          \App\Models\Popup::FREQUENCY_ONCE_PER_DAY => trans('app.popup_frequency_once_per_day'),
          \App\Models\Popup::FREQUENCY_ONCE_ONLY => trans('app.popup_frequency_once_only'),
      ], isset($popup) ? null : \App\Models\Popup::FREQUENCY_ONCE_PER_SESSION, ['class' => 'form-control']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>

  <div class="col-md-4 nopadding-right">
    <div class="form-group">
      {!! Form::label('delay_ms', trans('app.form.delay_ms'), ['class' => 'with-help']) !!}

      {!! Form::number('delay_ms', isset($popup) ? null : 2000, ['class' => 'form-control', 'min' => 0, 'step' => 500]) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>

  <div class="col-md-4 nopadding-left">
    <div class="form-group">
      {!! Form::label('priority', trans('app.priority'), ['class' => 'with-help']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.popup_priority') }}"></i>

      {!! Form::number('priority', isset($popup) ? null : 100, ['class' => 'form-control', 'min' => 1, 'max' => 999]) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-4 nopadding-right">
    <div class="form-group">
      {!! Form::label('starts_at', trans('app.form.starts_at'), ['class' => 'with-help']) !!}
      <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
        {!! Form::text('starts_at', null, ['class' => 'form-control datetimepicker']) !!}
      </div>
      <div class="help-block with-errors"></div>
    </div>
  </div>

  <div class="col-md-4 nopadding-right">
    <div class="form-group">
      {!! Form::label('ends_at', trans('app.form.ends_at'), ['class' => 'with-help']) !!}
      <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
        {!! Form::text('ends_at', null, ['class' => 'form-control datetimepicker']) !!}
      </div>
      <div class="help-block with-errors"></div>
    </div>
  </div>

  <div class="col-md-4 nopadding-left">
    <div class="form-group">
      {!! Form::label('bg_color', trans('app.form.text_color'), ['class' => 'with-help']) !!}
      <div class="input-group my-colorpicker2 colorpicker-element">
        {!! Form::text('bg_color', null, ['class' => 'form-control']) !!}
        <div class="input-group-addon"><i></i></div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <label class="with-help">{{ trans('app.image') }}</label>

      @if (isset($popup) && $popup->featureImage?->path && Storage::exists($popup->featureImage->path))
        <div>
          <img src="{{ get_storage_file_url($popup->featureImage->path, 'medium') }}" width="50%" alt="">

          <span class="indent10">
            {!! Form::checkbox('delete_image[feature]', 1, null, ['class' => 'icheck']) !!} {{ trans('app.form.delete_image') }}
          </span>
        </div>
        <div class="spacer5"></div>
      @endif

      <input type="file" name="images[feature]" />
      <div class="help-block with-errors"></div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="form-group">
      {!! Form::label('hide_text', trans('app.hide_popup_text')) !!}
      {!! Form::select('hide_text', [0 => trans('app.no'), 1 => trans('app.yes')], isset($popup) ? null : 0, ['class' => 'form-control']) !!}
      <p class="help-block small">{{ trans('help.popup_hide_text') }}</p>
    </div>

    <div class="form-group">
      <label class="with-help">{{ trans('app.active') }}</label>
      <div>
        {!! Form::checkbox('active', 1, isset($popup) ? null : true, ['class' => 'icheck']) !!}
        {{ trans('app.active') }}
      </div>
    </div>
  </div>
</div>

<p class="help-block">* {{ trans('app.form.required_fields') }}</p>
