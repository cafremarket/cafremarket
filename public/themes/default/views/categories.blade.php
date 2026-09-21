@extends('theme::layouts.main')

@section('content')
  <!-- CONTENT SECTION — Target-style shop-by-category grid -->
  @include('theme::contents.categories_page-new')

  <!-- Recently Viewed -->
  @include('theme::sections.recent_views')
@endsection
