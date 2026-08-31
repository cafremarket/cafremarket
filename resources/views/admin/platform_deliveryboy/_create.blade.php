@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.add_platform_rider') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', ['title' => trans('app.add_platform_rider'), 'icon' => 'fa-plus'])
    {!! Form::open(['route' => 'admin.admin.platform_rider.store', 'files' => true, 'data-toggle' => 'validator']) !!}
      @include('admin.deliveryboy._form')
      <button type="submit" class="btn btn-flat btn-new">{{ trans('app.form.save') }}</button>
    {!! Form::close() !!}
  @include('admin.partials.ui.card_end')
@endsection
