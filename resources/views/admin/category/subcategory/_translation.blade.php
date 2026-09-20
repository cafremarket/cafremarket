@extends('admin.layouts.master')

@section('content')
  {!! Form::model($subCategory_translation, ['method' => 'POST', 'route' => ['admin.catalog.subcategory.translate.store', $subCategory], 'files' => true, 'id' => 'form-ajax-upload', 'data-toggle' => 'validator']) !!}

  @include('admin.category.subcategory._translation_form')

  {!! Form::close() !!}
@endsection
