@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.edit') }} — {{ $meta['title'] }}
@endsection

@section('content')
  <div class="row">
    <div class="col-md-9">
      @include('admin.partials.ui.card_start', [
        'title' => $meta['title'],
        'icon' => 'fa-balance-scale',
        'actions' => '<a href="'.route('admin.utility.policyPage.index').'" class="btn btn-default btn-flat"><i class="fa fa-arrow-left"></i> '.e(trans('app.policy_pages')).'</a>',
      ])

      @if ($status === 'placeholder')
        <div class="alert alert-warning">
          <i class="fa fa-exclamation-triangle"></i>
          {{ trans('help.policy_page_placeholder_warning') }}
        </div>
      @endif

      {!! Form::model($page, [
        'method' => 'PUT',
        'route' => ['admin.utility.policyPage.update', $slug],
        'files' => true,
        'id' => 'policy-page-form',
        'data-toggle' => 'validator',
      ]) !!}

      <div class="row">
        <div class="col-md-8 nopadding-right">
          <div class="form-group">
            {!! Form::label('title', trans('app.form.page_title') . '*') !!}
            {!! Form::text('title', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.page_title'), 'required']) !!}
            <div class="help-block with-errors"></div>
          </div>
        </div>
        <div class="col-md-4 nopadding-left">
          <div class="form-group">
            {!! Form::label('position', trans('app.form.view_area') . '*') !!}
            {!! Form::select('position', $positions, null, ['class' => 'form-control select2-normal', 'required']) !!}
            <div class="help-block with-errors"></div>
          </div>
        </div>
      </div>

      <div class="form-group">
        {!! Form::label('slug', trans('app.form.slug')) !!}
        {!! Form::text('slug', $slug, ['class' => 'form-control', 'disabled' => true]) !!}
        <div class="help-block">{{ trans('help.policy_page_slug_locked') }}</div>
      </div>

      <div class="form-group">
        {!! Form::label('content', trans('app.form.content') . '*') !!}
        {!! Form::textarea('content', null, ['class' => 'form-control summernote-long', 'placeholder' => trans('app.placeholder.content'), 'required']) !!}
        <div class="help-block with-errors"></div>
      </div>

      <div class="row">
        <div class="col-md-6 nopadding-right">
          <div class="form-group">
            {!! Form::label('published_at', trans('app.form.publish_at')) !!}
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
              {!! Form::text('published_at', $page->published_at, ['class' => 'form-control datetimepicker', 'placeholder' => trans('app.placeholder.publish_at')]) !!}
            </div>
            <div class="help-block">{{ trans('help.leave_empty_to_save_as_draft') }}</div>
          </div>
        </div>
        <div class="col-md-6 nopadding-left">
          <div class="form-group">
            {!! Form::label('visibility', trans('app.form.visibility') . '*') !!}
            {!! Form::select('visibility', ['1' => trans('app.public'), '2' => trans('app.merchant')], null, ['class' => 'form-control select2-normal', 'required']) !!}
            <div class="help-block with-errors"></div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>{{ trans('app.cover_image') }}</label>
        @if ($page->coverImage)
          <div style="margin-bottom: 8px;">
            <img src="{{ get_storage_file_url(optional($page->coverImage)->path, 'small') }}" alt="{{ trans('app.cover_image') }}">
            <span style="margin-left: 10px;">
              {!! Form::checkbox('delete_image[cover]', 1, null, ['class' => 'icheck']) !!} {{ trans('app.form.delete_image') }}
            </span>
          </div>
        @endif
        <div class="row">
          <div class="col-md-9 nopadding-right">
            <input id="uploadFile" placeholder="{{ trans('app.cover_image') }}" class="form-control" disabled="disabled" style="height: 28px;" />
          </div>
          <div class="col-md-3 nopadding-left">
            <div class="fileUpload btn btn-primary btn-block btn-flat">
              <span>{{ trans('app.form.upload') }}</span>
              <input type="file" name="images[cover]" id="uploadBtn" class="upload" />
            </div>
          </div>
        </div>
      </div>

      <p class="help-block">* {{ trans('app.form.required_fields') }}</p>

      <div class="form-group">
        {!! Form::submit(trans('app.update'), ['class' => 'btn btn-flat btn-new']) !!}
        <a href="{{ route('admin.utility.policyPage.index') }}" class="btn btn-default btn-flat">{{ trans('app.cancel') }}</a>
      </div>

      {!! Form::close() !!}

      {!! Form::open(['route' => ['admin.utility.policyPage.applyDefaults', $slug], 'method' => 'POST', 'class' => 'data-form', 'style' => 'margin-top:10px;']) !!}
      <button type="submit" class="btn btn-warning btn-flat confirm" title="{{ trans('help.policy_apply_defaults') }}">
        <i class="fa fa-magic"></i> {{ trans('app.apply_default_content') }}
      </button>
      <span class="help-block" style="display:inline-block;margin-left:8px;">{{ trans('help.policy_apply_defaults') }}</span>
      {!! Form::close() !!}

      @include('admin.partials.ui.card_end')
    </div>

    <div class="col-md-3">
      @include('admin.partials.ui.card_start', [
        'title' => trans('app.used_by'),
        'icon' => 'fa-sitemap',
        'bodyClass' => 'admin-card__body--compact',
      ])

      <ul class="list-unstyled" style="margin-bottom: 0;">
        @foreach ($meta['channels'] as $channel)
          <li style="padding: 6px 0; border-bottom: 1px solid #f0f0f0;">
            <i class="fa fa-check-circle text-success"></i> {{ $channel }}
          </li>
        @endforeach
      </ul>

      <hr>
      <p class="small text-muted">{{ $meta['description'] }}</p>
      <p class="small">
        <strong>{{ trans('app.api') }}:</strong><br>
        <code>GET /api/page/{{ $slug }}</code>
      </p>
      @if ($page->slug)
        <p>
          <a href="{{ route('page.open', $page->slug) }}" target="_blank" class="btn btn-default btn-block btn-flat">
            <i class="fa fa-external-link"></i> {{ trans('app.go_to_page') }}
          </a>
        </p>
      @endif

      @include('admin.partials.ui.card_end')
    </div>
  </div>
@endsection
