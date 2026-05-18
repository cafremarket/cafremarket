@extends('theme::layouts.main')

@section('content')
  <!-- HEADER SECTION -->
  @include('theme::headers.order_detail')

  <!-- CONTENT SECTION -->
  @include('theme::contents.order_detail')

  <!-- BROWSING ITEMS -->
  @include('theme::sections.recent_views')
@endsection

@section('scripts')
  @if ($order->canResendEmolaPayment())
    <script>
      $(function() {
        'use strict';

        function emolaResendPopup(title, message, type) {
          if (typeof $.alert === 'function') {
            $.alert({
              title: title,
              content: message,
              type: type || 'green',
              icon: type === 'red' ? 'fas fa-times-circle' : 'fas fa-check-circle',
              class: 'flat',
              animation: 'scale',
              closeAnimation: 'scale',
              buttons: {
                ok: {
                  text: @json(trans('theme.button.ok')),
                  btnClass: 'btn-primary flat'
                }
              }
            });
            return;
          }

          if (typeof toastr !== 'undefined') {
            toastr.options = {
              closeButton: true,
              positionClass: 'toast-bottom-center'
            };
            toastr[type === 'red' ? 'error' : 'success'](message);
            return;
          }

          window.alert(title + '\n\n' + message);
        }

        function emolaResendErrorMessage(xhr) {
          var data = xhr.responseJSON;
          if (!data) {
            return @json(trans('theme.notify.failed'));
          }
          if (data.errors && data.errors.emola_number && data.errors.emola_number[0]) {
            return data.errors.emola_number[0];
          }
          return data.message || @json(trans('theme.notify.failed'));
        }

        $('body').on('submit', 'form.emola-resend-form', function(e) {
          e.preventDefault();
          $(this).find('.emola-resend-submit').trigger('click');
        });

        function emolaSyncPayment($btn, silent) {
          var url = $btn.data('url');
          if (!url) return;

          var originalHtml = $btn.html();
          $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

          $.ajax({
            url: url,
            method: 'POST',
            data: {
              _token: $('form.emola-resend-form input[name="_token"]').val() || $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(res) {
              if (res && res.paid) {
                emolaResendPopup(@json(trans('theme.success')), res.message, 'green');
                window.setTimeout(function() {
                  window.location.reload();
                }, 1500);
                return;
              }
              if (!silent) {
                emolaResendPopup(@json(trans('theme.warning') ?: trans('theme.error')), res.message || @json(trans('theme.emola_payment_pending')), 'orange');
              }
            },
            error: function(xhr) {
              if (!silent) {
                emolaResendPopup(@json(trans('theme.error')), emolaResendErrorMessage(xhr), 'red');
              }
            },
            complete: function() {
              $btn.prop('disabled', false).html(originalHtml);
            }
          });
        }

        $('body').on('click', '.emola-sync-payment', function(e) {
          e.preventDefault();
          emolaSyncPayment($(this), false);
        });

        var $syncBtn = $('.emola-sync-payment').first();
        if ($syncBtn.length) {
          window.setInterval(function() {
            emolaSyncPayment($syncBtn, true);
          }, 25000);
        }

        $('body').on('click', '.emola-resend-submit', function(e) {
          e.preventDefault();

          var $btn = $(this);
          if ($btn.prop('disabled')) {
            return;
          }

          var $form = $btn.closest('form.emola-resend-form');
          if (!$form.length) {
            return;
          }

          var $input = $form.find('#emola-resend-number');
          var number = ($input.val() || '').replace(/\D/g, '');

          if (!/^(86|87)\d{7}$/.test(number)) {
            emolaResendPopup(@json(trans('theme.error')), @json(trans('theme.emola_number_invalid')), 'red');
            $input.focus();
            return;
          }

          $input.val(number);

          if (typeof $.confirm !== 'function') {
            emolaResendPopup(@json(trans('theme.error')), @json(trans('theme.notify.failed')), 'red');
            return;
          }

          var msg = $btn.data('confirm') || @json(trans('theme.notify.are_you_sure'));

          $.confirm({
            title: @json(trans('theme.confirmation')),
            content: msg,
            type: 'red',
            icon: 'fas fa-question-circle',
            class: 'flat',
            animation: 'scale',
            closeAnimation: 'scale',
            opacity: 0.5,
            buttons: {
              confirm: {
                text: @json(trans('theme.button.proceed')),
                keys: ['enter'],
                btnClass: 'btn-primary flat',
                action: function() {
                  var originalHtml = $btn.html();
                  $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
                  $('body').css('cursor', 'wait');

                  $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: $form.serialize(),
                    dataType: 'json',
                    headers: {
                      'Accept': 'application/json',
                      'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(res) {
                      if (res && res.success) {
                        $input.val('');
                        emolaResendPopup(
                          @json(trans('theme.success')),
                          res.message || @json(trans('theme.emola_resend_success')),
                          'green'
                        );
                        return;
                      }

                      emolaResendPopup(
                        @json(trans('theme.error')),
                        (res && res.message) ? res.message : @json(trans('theme.emola_resend_failed')),
                        'red'
                      );
                    },
                    error: function(xhr) {
                      emolaResendPopup(
                        @json(trans('theme.error')),
                        emolaResendErrorMessage(xhr),
                        'red'
                      );
                    },
                    complete: function() {
                      $btn.prop('disabled', false).html(originalHtml);
                      $('body').css('cursor', 'default');
                    }
                  });

                  return true;
                }
              },
              cancel: {
                text: @json(trans('theme.button.cancel')),
                btnClass: 'btn-default flat'
              }
            }
          });
        });
      });
    </script>
  @endif
@endsection
