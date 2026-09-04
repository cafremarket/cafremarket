@extends('theme::layouts.main')

@section('content')
  @include('theme::headers.order_complete')
  @include('theme::contents.order_complete')
  @include('theme::sections.recent_views')
@endsection

@section('scripts')
  @include('scripts.order_transaction_schema')
@endsection
