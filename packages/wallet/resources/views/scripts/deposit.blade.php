<script src="https://js.stripe.com/v2/"></script>

<script type="text/javascript">
  "use strict";;
  (function($, window, document) {
    $(document).ready(function() {
      // i-Check plugin
      $('.i-check, .i-radio, .i-check-blue, .i-radio-blue').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass: 'iradio_minimal-blue',
      });

      $('.payment-option').on('ifChecked', function(e) {
        var code = $(this).data('code');
        $("#payment-instructions.text-danger").removeClass('text-danger').addClass('text-info small');
        $('#payment-instructions').children('span').html($(this).data('info'));

        // Alter checkout button text Stripe
        if ('stripe' == code && $(this).val() != 'saved_card') {
          showStripeCardForm();
        } else {
          hideStripeCardForm();
        }

        // Alter checkout button
        if ('paypal' == code) {
          $('#paypal-express-btn').removeClass('hide');
          $('#pay-now-btn').addClass('hide');
        } else {
          $('#paypal-express-btn').addClass('hide');
          $('#pay-now-btn').removeClass('hide');
        }

        toggleWalletMobileFields(code);
        refreshWalletTopupFeePreview();
      });

      // Submit the form
      $("a#paypal-express-btn").on('click', function(e) {
        e.preventDefault();
        $("form#depositForm").submit();
      });

      // Show cart form if the card option is selected
      var paymentOptionSelected = $('input[name="payment_method"]:checked');

      if (paymentOptionSelected.length > 0) {
        var code = paymentOptionSelected.data('code');

        if (code == 'stripe' && paymentOptionSelected.val() != 'saved_card') {
          showStripeCardForm();
        } else if ('paypal' == code) {
          $('#pay-now-btn').addClass('hide');
          $('#paypal-express-btn').removeClass('hide');
        }
        toggleWalletMobileFields(code);
      }

      // Stripe code, create a token
      Stripe.setPublishableKey("{{ config('services.stripe.key') }}");

      $("form#depositForm").on('submit', function(e) {
        e.preventDefault();

        var form = $(this);

        if (form.find("input[name='amount']").val() < 1) {
          return;
        }

        // Check if payment method has been selected or not
        if (!$("input:radio[name='payment_method']").is(":checked")) {
          $("#payment-instructions.text-info").removeClass('text-info small').addClass('text-danger');
          return;
        }

        var payment_method = $('input[name=payment_method]:checked').val();

        if (payment_method == 'stripe') {
          if (!$("input[data-stripe='number']").val() || !$("input[data-stripe='cvc']").val()) {
            return;
          }

          Stripe.card.createToken(form, function(status, response) {
            if (response.error) {
              form.find('.stripe-errors').text(response.error.message).removeClass('hide');
              remove_busy_filter('body');
            } else {
              form.append($('<input type="hidden" name="cc_token">').val(response.id));
              form.get(0).submit();
            }
          });
        } else {
          form.get(0).submit();
        }
      });

      $('#amount').on('input change', refreshWalletTopupFeePreview);

      $("#submit-btn-block").show();
      refreshWalletTopupFeePreview();
    });

    var walletFeePreviewUrl = $('#wallet-topup-fee-box').data('fee-url') || '{{ url('wallet/deposit/platform-fee') }}';

    function refreshWalletTopupFeePreview() {
      var box = $('#wallet-topup-fee-box');
      if (!box.length) return;

      var method = $('input[name=payment_method]:checked').val();
      var amount = parseFloat($('#amount').val());

      if (!method || (method !== 'mpesa' && method !== 'emola') || !amount || amount < 1) {
        box.hide();
        return;
      }

      $.get(walletFeePreviewUrl, { payment_method: method, amount: amount }, function(data) {
        var limitEl = $('#wallet-fee-emola-limit');
        var payBtn = $('#pay-now-btn');

        if (!data || !data.enabled || data.fee <= 0) {
          limitEl.hide().text('');
          payBtn.prop('disabled', false);
          box.hide();
          return;
        }
        $('#wallet-fee-base').text(data.formatted.base);
        $('#wallet-fee-amount').text(data.formatted.fee);
        $('#wallet-fee-total').text(data.formatted.total);
        $('#wallet-fee-row').show();
        box.show();

        if (method === 'emola' && data.exceeds_emola_limit && data.exceeds_message) {
          limitEl.text(data.exceeds_message).show();
          payBtn.prop('disabled', true);
        } else {
          limitEl.hide().text('');
          payBtn.prop('disabled', false);
        }
      }).fail(function() {
        $('#wallet-fee-emola-limit').hide();
        $('#pay-now-btn').prop('disabled', false);
        box.hide();
      });
    }

    function showStripeCardForm() {
      $('#cc-form').show().find('input:text, select').attr('required', 'required');
    }

    function hideStripeCardForm() {
      $('#cc-form').hide().find('input, select').removeAttr('required');
    }

    function toggleWalletMobileFields(code) {
      if ($('#mpesa-form').length) {
        if ('mpesa' == code) {
          $('#mpesa-form').show().find('input.mpesa-request-field').attr('required', 'required');
        } else {
          $('#mpesa-form').hide().find('input.mpesa-request-field').removeAttr('required');
        }
      }
      if ($('#emola-form').length) {
        if ('emola' == code) {
          $('#emola-form').show().find('input.emola-request-field').attr('required', 'required');
        } else {
          $('#emola-form').hide().find('input.emola-request-field').removeAttr('required');
        }
      }
    }
  }(window.jQuery, window, document));
</script>
