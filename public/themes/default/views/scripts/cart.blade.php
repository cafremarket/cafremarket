<script type="text/javascript">
  "use strict";;
  (function($, window, document) {
    $(document).ready(function() {
      // Check if specific cart is given
      var expressId = '{{ $expressId }}';

      if ('' != expressId && $('#cartId' + expressId).length) {
        $('#cartId' + expressId)[0].scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  }(window.jQuery, window, document));
</script>
