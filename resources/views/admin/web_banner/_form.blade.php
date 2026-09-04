@php
  $groupOptions = [
    'group_1' => trans('help.web_banner_group_group_1'),
    'group_2' => trans('help.web_banner_group_group_2'),
    'group_3' => trans('help.web_banner_group_group_3'),
    'group_4' => trans('help.web_banner_group_group_4'),
    'group_5' => trans('help.web_banner_group_group_5'),
    'group_6' => trans('help.web_banner_group_group_6'),
  ];
  $selectedGroup = old('group_id', isset($banner) ? $banner->group_id : ($defaultGroup ?? 'group_1'));
  $columnOptions = [
    '12' => trans('help.web_banner_width_full').' (12)',
    '8' => '8/12',
    '6' => trans('help.web_banner_width_half').' (6)',
    '4' => trans('help.web_banner_width_third').' (4)',
    '3' => trans('help.web_banner_width_quarter').' (3)',
  ];
@endphp

<div class="wb-form">
  <div class="wb-form__note">
    <i class="fa fa-info-circle"></i>
    <span>{{ trans('help.web_banner_form_note') }}</span>
  </div>

  <div class="wb-form__grid">
    <div class="wb-form__main">
      <div class="form-group">
        {!! Form::label('title', trans('app.form.title')) !!}
        {!! Form::text('title', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.title')]) !!}
        <p class="help-block small">{{ trans('app.tips') }}: {{ trans('app.add_span_tag') }}</p>
      </div>

      <div class="form-group">
        {!! Form::label('description', trans('app.form.description')) !!}
        {!! Form::text('description', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.banner_description')]) !!}
      </div>

      <div class="row">
        <div class="col-sm-7">
          <div class="form-group">
            {!! Form::label('link', trans('app.form.link')) !!}
            {!! Form::text('link', null, ['class' => 'form-control', 'placeholder' => 'https://']) !!}
          </div>
        </div>
        <div class="col-sm-5">
          <div class="form-group">
            {!! Form::label('link_label', trans('app.form.link_label')) !!}
            {!! Form::text('link_label', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.link_label')]) !!}
          </div>
        </div>
      </div>

      <div class="wb-form__layout-fields">
        <div class="form-group">
          {!! Form::label('group_id', trans('app.form.homepage_row').' *') !!}
          {!! Form::select('group_id', $groupOptions, $selectedGroup, ['class' => 'form-control', 'required']) !!}
        </div>
        <div class="form-group">
          {!! Form::label('columns', trans('app.form.columns')) !!}
          {!! Form::select('columns', $columnOptions, isset($banner) ? null : '12', ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
          {!! Form::label('order', trans('app.form.position')) !!}
          {!! Form::number('order', null, ['class' => 'form-control', 'min' => 0, 'placeholder' => '1']) !!}
        </div>
        <div class="form-group">
          {!! Form::label('effect', trans('app.zoom_effect')) !!}
          {!! Form::select('effect', [0 => trans('app.no'), 1 => trans('app.yes')], isset($banner) ? null : 0, ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
          {!! Form::label('hide_text', trans('app.hide_banner_text') ?? 'Hide text (image only)') !!}
          {!! Form::select('hide_text', [0 => trans('app.no'), 1 => trans('app.yes')], isset($banner) ? null : 0, ['class' => 'form-control']) !!}
          <p class="help-block small">{{ trans('help.web_banner_hide_text') ?? 'When Yes, only the banner image is shown — title and description stay hidden on the storefront.' }}</p>
        </div>
      </div>

      <div class="form-group wb-form__upload">
        <label for="uploadBtn">{{ trans('app.banner_image') }} *</label>
        @if (isset($banner) && $banner->featureImage)
          <div class="wb-form__preview">
            <img src="{{ get_storage_file_url(optional($banner->featureImage)->path, 'medium') }}" alt="">
            <label class="wb-form__delete-image">
              {!! Form::checkbox('delete_image[feature]', 1, null, ['class' => 'icheck']) !!}
              {{ trans('app.form.delete_image') }}
            </label>
          </div>
        @endif
        <div class="wb-form__file-row">
          <input type="text" id="uploadFile" class="form-control" readonly
                 placeholder="{{ trans('app.banner_image') }}"
                 value="{{ isset($banner) && $banner->featureImage ? (optional($banner->featureImage)->name ?? trans('app.banner_image')) : '' }}" />
          <label class="wb-form__upload-btn btn btn-primary btn-flat" for="uploadBtn">
            <i class="fa fa-upload"></i> {{ trans('app.form.upload') }}
          </label>
          <input type="file" name="images[feature]" id="uploadBtn" class="wb-form__file-input"
                 accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,.jpg,.jpeg,.png,.gif,.webp,.svg"
                 {{ isset($banner) ? '' : 'required' }} />
        </div>
        <p class="help-block small mb-0">{{ trans('help.web_banner_tip_image') }}</p>
      </div>

      <p class="help-block mb-0">* {{ trans('app.form.required_fields') }}</p>
    </div>

    <aside class="wb-form__aside">
      <div class="wb-form__aside-card">
        <h5>{{ trans('help.web_banner_tips_title') }}</h5>
        <ul>
          <li>{{ trans('help.web_banner_tip_row') }}</li>
          <li>{{ trans('help.web_banner_tip_width') }}</li>
          <li>{{ trans('help.web_banner_tip_order') }}</li>
          <li>{{ trans('help.web_banner_tip_image') }}</li>
        </ul>
      </div>
    </aside>
  </div>
</div>

<script>
(function () {
  var input = document.getElementById('uploadBtn');
  var nameField = document.getElementById('uploadFile');
  if (!input || !nameField) return;
  input.addEventListener('change', function () {
    var file = input.files && input.files[0];
    nameField.value = file ? file.name : '';
  });
})();
</script>