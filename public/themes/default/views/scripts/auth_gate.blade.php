{{-- Compulsory login + post-login address setup (no location picker on load) --}}
<script>
  (function($) {
    if (typeof $ === 'undefined') {
      return;
    }

    function cleanupModalOverlay() {
      $('.modal-backdrop').remove();
      $('body').removeClass('modal-open').css({ paddingRight: '', overflow: '' });
    }

    function openLoginModal() {
      var $login = $('#loginModal');
      if (!$login.length) {
        window.location.href = @json(route('homepage', ['login' => 1]));
        return;
      }

      cleanupModalOverlay();
      $login.modal({
        backdrop: 'static',
        keyboard: false,
        show: true
      });
    }

    function switchAuthModal(targetSelector) {
      var $target = $(targetSelector);
      if (!$target.length) {
        return;
      }

      var $open = $('.modal.in');
      if (!$open.length) {
        $target.modal({
          backdrop: 'static',
          keyboard: false,
          show: true
        });
        return;
      }

      if ($open.is(targetSelector)) {
        return;
      }

      $open.modal('hide');
      $open.one('hidden.bs.modal', function() {
        cleanupModalOverlay();
        $target.modal({
          backdrop: 'static',
          keyboard: false,
          show: true
        });
      });
    }

    function openAddressSetupModal(options) {
      options = options || {};
      var isOnboarding = options.onboarding === true;
      var url = @json(route('my.address.select'));
      var $modal = $('#myDynamicModal');

      if (!$modal.length || !url) {
        window.location.href = @json(route('account.addresses'));
        return;
      }

      $.get(url, function(data) {
        cleanupModalOverlay();
        $modal.attr('data-onboarding', isOnboarding ? '1' : '0');
        $modal.attr('data-modal-type', 'address-select');
        $modal.html(data).modal({
          backdrop: isOnboarding ? 'static' : true,
          keyboard: true,
          show: true
        });
      }).fail(function() {
        window.location.href = @json(route('account.addresses'));
      });
    }

    window.openCustomerLoginModal = openLoginModal;
    window.switchAuthModal = switchAuthModal;
    window.openAddressSetupModal = openAddressSetupModal;

    var loginDefaultError = @json(trans('auth.failed'));
    var loginSubmitLabel = @json(trans('theme.button.login'));

    function hideLoginModalError() {
      var $alert = $('#loginModalError');
      $alert.addClass('d-none').find('.sf-login-modal-alert__text').text('');
      $('#loginForm-1 .is-invalid').removeClass('is-invalid');
    }

    function showLoginModalError(message) {
      var $alert = $('#loginModalError');
      $alert.find('.sf-login-modal-alert__text').text(message || loginDefaultError);
      $alert.removeClass('d-none');
      $('#loginForm-1 .form-control').addClass('is-invalid');
    }

    function extractLoginErrorMessage(xhr) {
      var payload = xhr.responseJSON;

      if (!payload) {
        return loginDefaultError;
      }

      if (payload.message) {
        return payload.message;
      }

      if (payload.errors) {
        var firstKey = Object.keys(payload.errors)[0];
        if (firstKey && payload.errors[firstKey] && payload.errors[firstKey][0]) {
          return payload.errors[firstKey][0];
        }
      }

      return loginDefaultError;
    }

    $(document).on('submit', '#loginForm-1', function(e) {
      e.preventDefault();

      var $form = $(this);
      var $btn = $form.find('.js-login-submit');
      var csrf = $('meta[name="csrf-token"]').attr('content');

      hideLoginModalError();

      if (!$form[0].checkValidity()) {
        $form[0].reportValidity();
        return;
      }

      $btn.prop('disabled', true).addClass('is-loading');

      $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: $form.serialize(),
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      }).done(function(res) {
        window.location.href = (res && res.redirect) ? res.redirect : window.location.href;
      }).fail(function(xhr) {
        showLoginModalError(extractLoginErrorMessage(xhr));
        $form.find('#password').val('').trigger('focus');
      }).always(function() {
        $btn.prop('disabled', false).removeClass('is-loading');
      });
    });

    $(function() {
      var isPanelUser = {{ is_panel_user_on_storefront() ? 'true' : 'false' }};
      var isGuest = {{ Auth::guard('customer')->check() ? 'false' : 'true' }};
      var hasLocation = {{ buyer_has_location() ? 'true' : 'false' }};
      var needsAddress = {{ customer_needs_delivery_address() ? 'true' : 'false' }};
      var forceLogin = {{ (request()->boolean('login') || ($errors->any() && ! Auth::guard('customer')->check() && ! is_panel_user_on_storefront())) ? 'true' : 'false' }};

      if (!$('.modal.in').length) {
        cleanupModalOverlay();
      }

      $(document).on('click', '.js-auth-switch', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        if (target) {
          switchAuthModal(target);
        }
      });

      $(document).on('click', '.js-open-address-setup', function(e) {
        e.preventDefault();
        openAddressSetupModal({ onboarding: false });
      });

      $(document).on('click', '.js-close-address-select', function() {
        $('#myDynamicModal').modal('hide');
      });

      $(document).on('click', '.js-use-delivery-address', function(e) {
        e.preventDefault();

        var addressId = $(this).data('address-id');
        if (!addressId) {
          return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).addClass('is-loading');

        $.post(@json(url('my/address')) + '/' + addressId + '/use')
          .done(function(data) {
            if (data && data.success) {
              window.location.reload();
              return;
            }

            alert((data && data.message) ? data.message : @json(trans('theme.error')));
          })
          .fail(function(xhr) {
            var msg = @json(trans('theme.error'));
            if (xhr.responseJSON && xhr.responseJSON.message) {
              msg = xhr.responseJSON.message;
            }
            alert(msg);
          })
          .always(function() {
            $btn.prop('disabled', false).removeClass('is-loading');
          });
      });

      // Admin / Store panel session: never force customer login modal.
      if (isPanelUser) {
        return;
      }

      if (isGuest) {
        $('#loginModal, #createAccountModal, #passwordResetModal').on('shown.bs.modal', function() {
          $(this).find('.close').hide();
          if ($(this).is('#loginModal')) {
            hideLoginModalError();
            $(this).find('#email').trigger('focus');
          }
        });

        openLoginModal();
        return;
      }

      if (needsAddress && !hasLocation) {
        openAddressSetupModal({ onboarding: true });
      }
    });
  })(window.jQuery);
</script>

<style>
  .modal-backdrop {
    z-index: 10040 !important;
  }
  .modal {
    z-index: 10050 !important;
  }
  .modal.in {
    display: block !important;
  }
  #loginModal.modal.in,
  #createAccountModal.modal.in,
  #passwordResetModal.modal.in,
  #myDynamicModal.modal.in {
    display: flex !important;
    align-items: center;
    justify-content: center;
  }
  .sf-auth-link {
    color: var(--primary-color, #ff6600);
    font-weight: 600;
    font-size: 0.92rem;
    text-decoration: none;
  }
  .sf-auth-link:hover,
  .sf-auth-link:focus {
    color: var(--primary-dark, #cc5200);
    text-decoration: underline;
  }
  .sf-auth-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 18px 0 14px;
    color: #94a3b8;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
  }
  .sf-auth-divider::before,
  .sf-auth-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e2e8f0;
  }
  .sf-auth-register-btn {
    border: 1px solid #e2e8f0 !important;
    color: #334155 !important;
    font-weight: 600;
    background: #fff !important;
  }
  .sf-auth-register-btn:hover,
  .sf-auth-register-btn:focus {
    border-color: rgba(255, 102, 0, 0.35) !important;
    color: var(--primary-color, #ff6600) !important;
    background: #fff7ed !important;
  }
  .sf-auth-seller-login {
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid #e2e8f0;
    text-align: center;
  }
  .sf-auth-seller-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: var(--primary-color, #ff6600);
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
  }
  .sf-auth-seller-link:hover,
  .sf-auth-seller-link:focus {
    color: var(--primary-dark, #cc5200);
    text-decoration: underline;
  }
  .sf-login-modal-alert {
    margin-bottom: 16px;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 0.9rem;
    line-height: 1.45;
  }
  .sf-login-modal-alert .fa {
    margin-right: 6px;
  }
  #loginModal .form-control.is-invalid {
    border-color: #dc3545;
  }
  .js-login-submit.is-loading {
    opacity: 0.75;
    pointer-events: none;
  }
  @media (max-width: 575px) {
    #loginModal .modal-dialog,
    #createAccountModal .modal-dialog,
    #passwordResetModal .modal-dialog,
    #myDynamicModal .modal-dialog {
      width: calc(100% - 20px);
      margin: 10px auto;
    }
  }
</style>
