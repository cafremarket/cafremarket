@php
  use App\Models\Banner;

  $groupOptions = [
    'group_1' => trans('help.web_banner_group_group_1'),
    'group_2' => trans('help.web_banner_group_group_2'),
    'group_3' => trans('help.web_banner_group_group_3'),
  ];
  $selectedGroup = old('group_id', isset($banner) ? $banner->group_id : ($defaultGroup ?? 'group_1'));
  if (! array_key_exists($selectedGroup, $groupOptions)) {
    $selectedGroup = 'group_1';
  }

  $layoutOptions = [
    (string) Banner::LAYOUT_FULL => trans('help.web_banner_layout_full'),
    (string) Banner::LAYOUT_THIRD => trans('help.web_banner_layout_third'),
  ];

  $typeOptions = [
    Banner::TYPE_SINGLE => trans('app.banner_type_single'),
    Banner::TYPE_SLIDER => trans('app.banner_type_slider'),
    Banner::TYPE_COLOUR => trans('app.banner_type_colour'),
  ];

  $selectedType = old('display_type', isset($banner) ? ($banner->display_type ?: Banner::TYPE_SINGLE) : Banner::TYPE_SINGLE);
  $selectedLayout = old('columns', isset($banner) ? (string) ($banner->columns ?: Banner::LAYOUT_FULL) : (string) Banner::LAYOUT_FULL);
  if (! array_key_exists($selectedLayout, $layoutOptions)) {
    $selectedLayout = (string) Banner::LAYOUT_FULL;
  }
  $selectedColor = old('bg_color', isset($banner) ? ($banner->bg_color ?: '#f97316') : '#f97316');
  $formNote = $formNote ?? trans('help.web_banner_form_note');
@endphp

<div class="wb-form">
  <div class="wb-form__note">
    <i class="fa fa-info-circle"></i>
    <span>{{ $formNote }}</span>
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
          {!! Form::label('display_type', trans('app.banner_display_type').' *') !!}
          {!! Form::select('display_type', $typeOptions, $selectedType, ['class' => 'form-control', 'id' => 'wb_display_type', 'required']) !!}
          <p class="help-block small">{{ trans('help.web_banner_display_type') }}</p>
        </div>

        <div class="form-group" id="wb_layout_field">
          {!! Form::label('columns', trans('app.banner_layout').' *') !!}
          {!! Form::select('columns', $layoutOptions, $selectedLayout, ['class' => 'form-control', 'id' => 'wb_columns', 'required']) !!}
          <p class="help-block small">{{ trans('help.web_banner_layout') }}</p>
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
          {!! Form::label('hide_text', trans('app.hide_banner_text')) !!}
          {!! Form::select('hide_text', [0 => trans('app.no'), 1 => trans('app.yes')], isset($banner) ? null : 0, ['class' => 'form-control']) !!}
          <p class="help-block small">{{ trans('help.web_banner_hide_text') }}</p>
        </div>
      </div>

      <div class="form-group wb-form__colour-field" id="wb_colour_field" style="{{ $selectedType === Banner::TYPE_COLOUR ? '' : 'display:none;' }}">
        {!! Form::label('bg_color', trans('app.background').' *') !!}
        <div class="wb-form__color-row">
          <input type="color" id="wb_bg_color_picker" value="{{ preg_match('/^#[0-9A-Fa-f]{6}$/', $selectedColor) ? $selectedColor : '#f97316' }}" title="{{ trans('app.background') }}">
          {!! Form::text('bg_color', $selectedColor, ['class' => 'form-control', 'id' => 'wb_bg_color', 'placeholder' => '#f97316']) !!}
        </div>
        <p class="help-block small">{{ trans('help.web_banner_colour') }}</p>
      </div>

      <div class="form-group wb-form__upload" id="wb_image_field">
        <label for="uploadBtn">
          {{ trans('app.banner_image') }}
          <span id="wb_image_required_mark">*</span>
        </label>
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
                 {{ isset($banner) || $selectedType === Banner::TYPE_COLOUR ? '' : 'required' }} />
        </div>
        <p class="help-block small mb-0" id="wb_image_help">{{ trans('help.web_banner_tip_image') }}</p>
      </div>

      <p class="help-block mb-0">* {{ trans('app.form.required_fields') }}</p>
    </div>

    <aside class="wb-form__aside">
      <div class="wb-form__aside-card">
        <h5>{{ trans('help.web_banner_tips_title') }}</h5>
        <ul>
          <li>{{ trans('help.web_banner_tip_row') }}</li>
          <li>{{ trans('help.web_banner_tip_type') }}</li>
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
  var typeSelect = document.getElementById('wb_display_type');
  var layoutField = document.getElementById('wb_layout_field');
  var colourField = document.getElementById('wb_colour_field');
  var imageRequiredMark = document.getElementById('wb_image_required_mark');
  var imageHelp = document.getElementById('wb_image_help');
  var colorPicker = document.getElementById('wb_bg_color_picker');
  var colorText = document.getElementById('wb_bg_color');
  var isEdit = {{ isset($banner) ? 'true' : 'false' }};

  if (input && nameField) {
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      nameField.value = file ? file.name : '';
    });
  }

  function syncTypeUi() {
    if (!typeSelect) return;
    var type = typeSelect.value;
    var isColour = type === 'colour';
    var isSlider = type === 'slider';

    if (colourField) {
      colourField.style.display = isColour ? '' : 'none';
    }
    if (layoutField) {
      layoutField.style.display = isSlider ? 'none' : '';
    }
    if (imageRequiredMark) {
      imageRequiredMark.style.display = isColour ? 'none' : '';
    }
    if (imageHelp) {
      imageHelp.textContent = isColour
        ? @json(trans('help.web_banner_tip_image_optional'))
        : @json(trans('help.web_banner_tip_image'));
    }
    if (input && !isEdit) {
      if (isColour) {
        input.removeAttribute('required');
      } else {
        input.setAttribute('required', 'required');
      }
    }
  }

  if (typeSelect) {
    typeSelect.addEventListener('change', syncTypeUi);
    syncTypeUi();
  }

  if (colorPicker && colorText) {
    colorPicker.addEventListener('input', function () {
      colorText.value = colorPicker.value;
    });
    colorText.addEventListener('input', function () {
      if (/^#[0-9A-Fa-f]{6}$/.test(colorText.value)) {
        colorPicker.value = colorText.value;
      }
    });
  }
})();
</script>
