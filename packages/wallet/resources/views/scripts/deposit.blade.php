<script type="text/javascript">
  "use strict";;
  (function($, window, document) {
    $(document).ready(function() {
      $('.i-check, .i-radio, .i-check-blue, .i-radio-blue').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass: 'iradio_minimal-blue',
      });

      $('.payment-option').on('ifChecked', function(e) {
        var code = $(this).data('code');
        $("#payment-instructions.text-danger").removeClass('text-danger').addClass('text-info small');
        $('#payment-instructions').children('span').html($(this).data('info'));
        toggleWalletMobileFields(code);
        // Gateway switch: refresh fee immediately for the current amount.
        refreshWalletTopupFeePreview({ immediate: true });
      });

      var paymentOptionSelected = $('input[name="payment_method"]:checked');

      if (paymentOptionSelected.length > 0) {
        toggleWalletMobileFields(paymentOptionSelected.data('code'));
      }

      $("form#depositForm").on('submit', function(e) {
        e.preventDefault();

        var form = $(this);

        if (form.find("input[name='amount']").val() < 1) {
          return;
        }

        if (!$("input:radio[name='payment_method']").is(":checked")) {
          $("#payment-instructions.text-info").removeClass('text-info small').addClass('text-danger');
          return;
        }

        form.get(0).submit();
      });

      // input/change cover typing; blur/paste/keyup help autofill browsers.
      $('#amount').on('input change blur paste keyup', function() {
        scheduleWalletTopupFeePreview();
      });

      // Autofill can set the value without reliable input events — poll briefly.
      var autofillPolls = 0;
      var autofillTimer = setInterval(function() {
        autofillPolls += 1;
        var current = String($('#amount').val() || '');
        if (current !== lastSeenAmount) {
          lastSeenAmount = current;
          scheduleWalletTopupFeePreview();
        }
        if (autofillPolls >= 20) {
          clearInterval(autofillTimer);
        }
      }, 400);

      $("#submit-btn-block").show();
      refreshWalletTopupFeePreview({ immediate: true });
    });

    var walletFeePreviewUrl = $('#wallet-topup-fee-box').data('fee-url') || '{{ url('wallet/deposit/platform-fee') }}';
    var walletFeePreviewSeq = 0;
    var walletFeePreviewTimer = null;
    var lastSeenAmount = String($('#amount').val() || '');

    function selectedPaymentMethod() {
      var $checked = $('input[name=payment_method]:checked');
      if (!$checked.length) {
        $checked = $('input.payment-option').filter(function() {
          return $(this).prop('checked') || $(this).parent().hasClass('checked');
        }).first();
      }
      return $checked.val() || $checked.data('code') || '';
    }

    function scheduleWalletTopupFeePreview() {
      clearTimeout(walletFeePreviewTimer);
      walletFeePreviewTimer = setTimeout(function() {
        refreshWalletTopupFeePreview({ immediate: false });
      }, 280);
    }

    function refreshWalletTopupFeePreview(opts) {
      opts = opts || {};
      var box = $('#wallet-topup-fee-box');
      if (!box.length) return;

      var method = selectedPaymentMethod();
      var amount = parseFloat($('#amount').val());
      lastSeenAmount = String($('#amount').val() || '');

      if (!method || (method !== 'mpesa' && method !== 'emola') || !amount || amount < 1) {
        box.hide();
        $('#wallet-fee-emola-limit').hide().text('');
        $('#pay-now-btn').prop('disabled', false);
        return;
      }

      // Hide only while waiting for a new amount-typed preview; keep visible
      // during gateway switches so the UI does not flash empty.
      if (!opts.immediate) {
        box.hide();
      }

      var seq = ++walletFeePreviewSeq;
      $.get(walletFeePreviewUrl, { payment_method: method, amount: amount }, function(data) {
        if (seq !== walletFeePreviewSeq) return;
        var currentAmount = parseFloat($('#amount').val());
        if (!currentAmount || Math.abs(currentAmount - amount) > 0.009) return;
        var currentMethod = selectedPaymentMethod();
        if (currentMethod !== method) return;

        var limitEl = $('#wallet-fee-emola-limit');
        var payBtn = $('#pay-now-btn');

        if (!data || !data.enabled) {
          limitEl.hide().text('');
          payBtn.prop('disabled', false);
          box.hide();
          return;
        }
        $('#wallet-fee-base').text(data.formatted.base);
        $('#wallet-fee-amount').text(data.formatted.fee);
        $('#wallet-fee-total').text(data.formatted.total);
        if (data.fee > 0) {
          $('#wallet-fee-row').show();
          box.show();
        } else {
          $('#wallet-fee-row').hide();
          box.hide();
        }

        if (method === 'emola' && data.exceeds_emola_limit && data.exceeds_message) {
          limitEl.text(data.exceeds_message).show();
          payBtn.prop('disabled', true);
          box.show();
        } else {
          limitEl.hide().text('');
          payBtn.prop('disabled', false);
        }
      }).fail(function() {
        if (seq !== walletFeePreviewSeq) return;
        $('#wallet-fee-emola-limit').hide();
        $('#pay-now-btn').prop('disabled', false);
        box.hide();
      });
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
