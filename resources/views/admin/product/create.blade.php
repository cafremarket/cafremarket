@extends('admin.layouts.master')

@section('content')
  {!! Form::open(['route' => 'admin.catalog.product.store', 'files' => true, 'id' => 'form-ajax-upload', 'data-toggle' => 'validator']) !!}

  @include('admin.product._form')

  {!! Form::close() !!}
@endsection

@section('page-script')
  @include('plugins.dropzone-upload')
  @include('plugins.dynamic-inputs')
  @if (is_incevio_package_loaded('aiAssistant'))
    @include('aiAssistant::_generation_btn_script')
  @endif
@endsection
