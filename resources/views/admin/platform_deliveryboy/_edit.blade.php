@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.edit_platform_rider') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', ['title' => trans('app.edit_platform_rider'), 'icon' => 'fa-edit'])
    {!! Form::model($deliveryboy, ['route' => ['admin.admin.platform_rider.update', $deliveryboy], 'method' => 'PUT', 'files' => true, 'data-toggle' => 'validator']) !!}
      @include('admin.deliveryboy._form')
      <button type="submit" class="btn btn-flat btn-new">{{ trans('app.update') }}</button>
    {!! Form::close() !!}
  @include('admin.partials.ui.card_end')
@endsection
