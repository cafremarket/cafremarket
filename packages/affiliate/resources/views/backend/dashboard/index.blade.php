@extends('affiliate::backend.master_layout')

@section('page-style')
  @include('plugins.ionic')
@endsection

@section('content')
  @include('affiliate::backend.dashboard._top_cards')

  @include('affiliate::backend.dashboard._chart')

  @include('affiliate::backend.dashboard._ranking_lists')
@endsection
