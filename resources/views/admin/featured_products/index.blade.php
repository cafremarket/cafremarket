@extends('admin.layouts.master')

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.featured_items'),
    'icon' => 'fa-star',
  ])
      <p class="text-muted">
        {{ trans('help.featured_products_intro') }}
        {{ trans('help.featured_products_pick') }}
      </p>
      <div class="spacer10"></div>
      {!! Form::open(['route' => 'admin.featuredProducts.update', 'class' => 'form-horizontal', 'id' => 'form']) !!}
      <div class="row">
        <div class="col-sm-10">
          <div class="form-group">
            <div class="col-sm-3 text-right">
              {!! Form::label('featured', trans('app.featured_items') . ':', ['class' => 'control-label']) !!}
            </div>
            <div class="col-sm-9 nopadding-left">
              @include('admin.partials.product_picker_modal', [
                'mode' => 'multiple',
                'inputName' => 'featured[]',
                'selected' => $items,
                'buttonLabel' => 'Add products from a store',
                'pickerId' => 'featuredPicker',
              ])
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
