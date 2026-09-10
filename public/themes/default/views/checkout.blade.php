@extends('theme::layouts.main')

@section('content')
  <!-- breadcrumb -->
  @include('theme::headers.checkout_page')

  <!-- CONTENT SECTION -->
  @include('theme::contents.checkout_page')
@endsection

@section('scripts')
  @include('scripts.checkout')
  @include('theme::scripts.cart_pricing')
  @include('theme::scripts.dynamic_checkout')
  <script type="text/javascript">
    "use strict";
    (function($) {
      if ($.fn.popover) {
        $('.shipping-breakdown-info, .tax-breakdown-info').popover({
          container: 'body',
          html: true,
          trigger: 'hover focus click'
        });
      }
    }(window.jQuery));
  </script>
@endsection
