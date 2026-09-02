<script src="https://js.stripe.com/v2/"></script>
<script type="text/javascript">
  "use strict";;
  (function($, window, document) {
    $(document).ready(function() {
      // Check if customer exist
      var vendor_get_paid_directly = {{ vendor_get_paid_directly() ? 'true' : 'undefined' }};
      var customer = {{ $customer ? 'true' : 'undefined' }};

      // Show email/password form is customer want to save the card/create account
      if ($("#create-account-checkbox, #remember-the-card").is(':checked')) {
        showAccountForm();
      }

      // Toggle account creation fields
      $('#create-account-checkbox, #remember-the-card').on('ifChecked', function() {
        $('#create-account-checkbox').iCheck('check');
        showAccountForm();
      });
      $('#create-account-checkbox').on('ifUnchecked', function() {
        $('#remember-the-card').iCheck('uncheck');
        $('#create-account').hide().find('input[type=email],input[type=password]').removeAttr('required');
      });

      $('.payment-option').on('ifChecked', function(e) {
        var code = $(this).data('code');
        $("#payment-instructions.text-danger").removeClass('text-danger').addClass('text-info small');
        $('#payment-instructions').children('span').html($(this).data('info'));

        // Alter checkout button
        if ('paypal' == code) {
          $('#paypal-express-btn').removeClass('hide');
          $('#pay-now-btn').addClass('hide');
        } else {
          $('#paypal-express-btn').addClass('hide');
          $('#pay-now-btn').removeClass('hide');
        }

        // mpesa form
        if ('mpesa' == code) {
          showMPesaForm();
        } else {
          hideMPesaForm();
        }

        // eMola form
        if ('emola' == code) {
          showEmolaForm();
        } else {
          hideEmolaForm();
        }

        refreshCheckoutPlatformFeePreview();

        // Bank transfer proof upload
        if ('wire' == code) {
          showWireTransferProof();
        } else {
          hideWireTransferProof();
        }
      });

      // Submit the form
      $("a#paypal-express-btn").on('click', function(e) {
        e.preventDefault();
        $("form[name='checkoutForm']").submit();
      });

      // Show cart form if the card option is selected
      var paymentOptionSelected = $('input[name="payment_method"]:checked');

      if (paymentOptionSelected.length > 0) {
        var code = paymentOptionSelected.data('code');

        if ('paypal' == code) {
          $('#pay-now-btn').addClass('hide');
          $('#paypal-express-btn').removeClass('hide');
        } else if ('mpesa' == code) {
          showMPesaForm();
        } else if ('emola' == code) {
          showEmolaForm();
        } else if ('wire' == code) {
          showWireTransferProof();
        }

        refreshCheckoutPlatformFeePreview();
      }

      setTimeout(function() {
        refreshCheckoutPlatformFeePreview();
      }, 400);

      // Stripe code, create a token
      Stripe.setPublishableKey("{{ config('services.stripe.key') }}");

      $("form[name='checkoutForm']").on('submit', function(e) {
        e.preventDefault();

        var form = $(this);

        // Check if payment method has been selected or not
        if (!$("input:radio[name='payment_method']").is(":checked")) {
          $("#payment-instructions.text-info").removeClass('text-info small').addClass('text-danger');
          return;
        }

        // If customer exist the check shipping address is seleced
        if (typeof customer !== "undefined") {
          if (!$("input:radio[name='ship_to']").is(":checked")) {
            $('.address-list-item').addClass('has-error');
            $('#ship-to-error-block').html("{{ trans('theme.notify.select_shipping_address') }}");
            return;
          }
        }
        // check if warehouse has been selected for pickup order
        if ($("#fulfilment_type_pickup").is(":checked") && !$('.warehouse_id:checked').length) {
          @include('theme::layouts.notification', ['message' => trans('theme.notify.select_pickup_address'), 'type' => 'warning', 'icon' => 'times-circle'])
          return;
        }

        // Check if form validation pass
        if ($(".has-error").length) {
          @include('theme::layouts.notification', ['message' => trans('theme.notify.fill_required_info'), 'type' => 'warning', 'icon' => 'times-circle'])
          return;
        }

        apply_busy_filter('body');

        var payment_method = $('input[name=payment_method]:checked').data('code');

        if (payment_method == 'stripe') {
          form.get(0).submit();
        } else {
          form.get(0).submit();
        }
      });

      $("#submit-btn-block").show(); // Show the submit buttons after loading the doms
    });

    function showAccountForm() {
      $('#create-account').show().find('input[type=email],input[type=password]').attr('required', 'required');
    }

    // M-Pesa Payment
    function showMPesaForm() {
      $('#mpesa-form').show().find('input.mpesa-request-field').attr('required', 'required');
    }

    function hideMPesaForm() {
      $('#mpesa-form').hide().find('input.mpesa-request-field').removeAttr('required');
    }

    function showEmolaForm() {
      $('#emola-form').show().find('input.emola-request-field').attr('required', 'required');
    }

    function hideEmolaForm() {
      $('#emola-form').hide().find('input.emola-request-field').removeAttr('required');
    }

    var checkoutFeePreviewUrl = $('#checkout-platform-fee-box').attr('data-fee-url') || '{{ url('wallet/checkout/platform-fee') }}';

    function refreshCheckoutPlatformFeePreview(cartId, grandAmount) {
      var box = $('#checkout-platform-fee-box');
      if (!box.length) {
        return;
      }

      cartId = cartId || $('#checkout-id').val();
      var selected = $('input[name=payment_method]:checked');
      var method = selected.data('code') || selected.val();

      if (!method || (method !== 'mpesa' && method !== 'emola')) {
        box.hide();
        $('#checkout-summary-customer-fee-li' + cartId).hide();
        $('#checkout-summary-pay-total-li' + cartId).hide();
        return;
      }

      var amount = grandAmount;
      if (amount === undefined || amount === null) {
        var raw = $('#summary-grand-total' + cartId).text().replace(/[^\d.-]/g, '');
        amount = parseFloat(raw);
      }

      if (!amount || amount <= 0 || isNaN(amount)) {
        box.hide();
        $('#checkout-summary-customer-fee-li' + cartId).hide();
        $('#checkout-summary-pay-total-li' + cartId).hide();
        return;
      }

      var shopId = $('#shop-id' + cartId).val();
      var previewParams = { payment_method: method, amount: amount };
      if (shopId) {
        previewParams.shop_id = shopId;
      }

      $.getJSON(checkoutFeePreviewUrl, previewParams, function(data) {
        if (!data) {
          box.hide();
          $('#checkout-summary-customer-fee-li' + cartId).hide();
          $('#checkout-summary-pay-total-li' + cartId).hide();
          return;
        }

        $('#checkout-fee-base').text(data.formatted.base);
        $('#checkout-fee-amount').text(data.formatted.fee);
        $('#checkout-fee-total').text(data.formatted.total);

        if (data.fee > 0) {
          $('#checkout-fee-row').show();
        } else {
          $('#checkout-fee-row').hide();
        }

        box.show();
        $('#checkout-summary-customer-fee' + cartId).text(data.formatted.fee);
        $('#checkout-summary-pay-total' + cartId).text(data.formatted.total);
        $('#checkout-summary-customer-fee-li' + cartId).show();
        $('#checkout-summary-pay-total-li' + cartId).show();
      }).fail(function() {
        box.hide();
        $('#checkout-summary-customer-fee-li' + cartId).hide();
        $('#checkout-summary-pay-total-li' + cartId).hide();
      });
    }

    window.refreshCheckoutPlatformFeePreview = refreshCheckoutPlatformFeePreview;

    function showWireTransferProof() {
      $('#wire-transfer-proof-wrap').removeClass('hide');
      $('#wire-transfer-proof').attr('required', 'required');
    }

    function hideWireTransferProof() {
      $('#wire-transfer-proof-wrap').addClass('hide');
      $('#wire-transfer-proof').removeAttr('required').val('');
    }
  }(window.jQuery, window, document));
</script>
