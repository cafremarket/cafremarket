@extends('theme::layouts.main')

@section('content')
  @include('theme::headers.cart_page')
  @include('theme::contents.cart_page')
  @include('theme::sections.recent_views')
@endsection

@section('scripts')
  @include('theme::modals.ship_to')
  @include('theme::scripts.cart')

  @if (is_incevio_package_loaded('wholesale'))
    @include('wholesale::scripts.cart_page_script')
  @endif

  @include('theme::scripts.cart_pricing')
  @include('theme::scripts.dynamic_checkout')

  @if (($activeCart ?? null) || (isset($carts) && $carts->count() > 0))
    @include('scripts.checkout')
  @endif

  <script type="text/javascript">
    "use strict";
    (function($) {
      $(document).on('click', '.sf-checkout__change-addr', function() {
        var target = $(this).data('target');
        $(target).slideToggle(180);
      });

      // Shipping / tax breakdown info (hover / click)
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
