@extends('admin.layouts.master')

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.featured_categories'),
    'icon' => 'fa-star',
  ])
      <p class="text-muted">
        {{ trans('help.featured_categories') }}
      </p>
      <div class="spacer10"></div>
      {!! Form::open(['route' => 'admin.featuredCategories.update', 'class' => 'form-horizontal', 'id' => 'form']) !!}
      <div class="row">
        <div class="col-sm-10">
          <div class="form-group">
            <div class="col-sm-3 text-right">
              {!! Form::label('featured', trans('app.featured_categories') . ':', ['class' => 'control-label']) !!}
            </div>
            <div class="col-sm-9 nopadding-left">
              {!! Form::select('featured[]', $categories, $selected, ['class' => 'form-control select2-normal', 'multiple' => 'multiple']) !!}
              <div class="help-block with-errors"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-10">
          {!! Form::submit(trans('app.update'), ['class' => 'btn btn-lg btn-flat btn-new pull-right']) !!}
        </div>
      </div>
      {!! Form::close() !!}
  @include('admin.partials.ui.card_end')
@endsection
