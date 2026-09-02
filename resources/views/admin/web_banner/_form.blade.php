@php
  $groupOptions = [
    'group_1' => trans('help.web_banner_group_group_1'),
    'group_2' => trans('help.web_banner_group_group_2'),
    'group_3' => trans('help.web_banner_group_group_3'),
    'group_4' => trans('help.web_banner_group_group_4'),
    'group_5' => trans('help.web_banner_group_group_5'),
    'group_6' => trans('help.web_banner_group_group_6'),
  ];
  $selectedGroup = old('group_id', isset($banner) ? $banner->group_id : ($defaultGroup ?? null));
@endphp

<div class="row">
  <div class="col-md-9 nopadding-right">
    <div class="alert alert-light border small mb-3">
      <i class="fa fa-home text-primary"></i>
      {{ trans('help.web_banner_form_note') }}
    </div>

    <div class="row">
      <div class="col-md-9 nopadding-right">
        <div class="form-group">
          {!! Form::label('title', trans('app.form.title'), ['class' => 'with-help']) !!}
          <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.banner_title') }}"></i>
          {!! Form::text('title', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.title')]) !!}
          <div class="help-block with-errors">
            <i class="fa fa-info"></i> {{ trans('app.tips') . ': ' . trans('app.add_span_tag') }}
          </div>
        </div>
      </div>

      <div class="col-md-3 nopadding-left">
        <div class="form-group">
          {!! Form::label('effect', trans('app.zoom_effect'), ['class' => 'with-help']) !!}
          {!! Form::select('effect', [0 => trans('app.no'), 1 => trans('app.yes')], isset($banner) ? null : 0, ['class' => 'form-control select2-normal', 'placeholder' => trans('app.zoom_effect')]) !!}
        </div>
      </div>
    </div>

    <div class="form-group">
      {!! Form::label('description', trans('app.form.description'), ['class' => 'with-help']) !!}
      {!! Form::text('description', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.banner_description')]) !!}
    </div>

    <div class="row">
      <div class="col-md-6 nopadding-right">
        <div class="form-group">
          {!! Form::label('link', trans('app.form.link'), ['class' => 'with-help']) !!}
          {!! Form::text('link', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.link')]) !!}
        </div>
      </div>
      <div class="col-md-6 nopadding-left">
        <div class="form-group">
          {!! Form::label('link_label', trans('app.form.link_label'), ['class' => 'with-help']) !!}
          {!! Form::text('link_label', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.link_label')]) !!}
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4 nopadding-right">
        <div class="form-group">
          {!! Form::label('group_id', trans('app.form.homepage_row') . '*', ['class' => 'with-help']) !!}
          {!! Form::select('group_id', $groupOptions, $selectedGroup, ['class' => 'form-control select2-normal', 'placeholder' => trans('app.placeholder.group'), 'required']) !!}
          <div class="help-block with-errors"></div>
        </div>
      </div>
      <div class="col-md-4 nopadding-left nopadding-right">
        <div class="form-group">
          {!! Form::label('columns', trans('app.form.columns'), ['class' => 'with-help']) !!}
          {!! Form::select('columns', ['3' => 3, '4' => 4, '6' => 6, '8' => 8, '12' => 12], isset($banner) ? null : 4, ['class' => 'form-control select2-normal', 'placeholder' => trans('app.placeholder.columns')]) !!}
          <p class="help-block small text-muted mb-0">{{ trans('help.web_banner_columns_hint') }}</p>
        </div>
      </div>
      <div class="col-md-4 nopadding-left">
        <div class="form-group">
          {!! Form::label('order', trans('app.form.position'), ['class' => 'with-help']) !!}
          {!! Form::number('order', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.position')]) !!}
        </div>
      </div>
    </div>

    <div class="form-group">
      <label class="with-help">{{ trans('app.banner_image') }} *</label>
      @if (isset($banner) && $banner->featureImage)
        <div class="mb-2">
          <img src="{{ get_storage_file_url(optional($banner->featureImage)->path, 'small') }}" class="admin-table__banner-thumb" alt="">
          {!! Form::checkbox('delete_image[feature]', 1, null, ['class' => 'icheck']) !!} {{ trans('app.form.delete_image') }}
        </div>
      @endif
      <div class="row">
        <div class="col-md-9 nopadding-right">
          <input id="uploadFile" placeholder="{{ trans('app.banner_image') }}" class="form-control" disabled="disabled" style="height: 28px;" />
        </div>
        <div class="col-md-3 nopadding-left">
          <div class="fileUpload btn btn-primary btn-block btn-flat">
            <span>{{ trans('app.form.upload') }}</span>
            <input type="file" name="images[feature]" id="uploadBtn" class="upload" {{ isset($banner) ? '' : 'required' }} accept="image/*" />
          </div>
        </div>
      </div>
    </div>

    <p class="help-block">* {{ trans('app.form.required_fields') }}</p>
  </div>

  <div class="col-md-3 nopadding-left hidden-xs">
    <img src="{{ asset('images/placeholders/banner_layout.jpg') }}" width="100%" alt="{{ trans('nav.web_banners') }}">
  </div>
</div>
