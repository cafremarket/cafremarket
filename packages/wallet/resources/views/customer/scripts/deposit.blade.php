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
        refreshWalletTopupFeePreview();
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
