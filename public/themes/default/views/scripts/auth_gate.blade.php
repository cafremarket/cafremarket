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

    function openAddressSetupModal() {
      var url = @json(route('my.address.create'));
      var $modal = $('#myDynamicModal');

      if (!$modal.length || !url) {
        window.location.href = @json(route('account', 'account')) + '#address-tab';
        return;
      }

      $.get(url, function(data) {
        cleanupModalOverlay();
        $modal.attr('data-onboarding', '1');
        $modal.html(data).modal({
          backdrop: 'static',
          keyboard: false,
          show: true
        });
      }).fail(function() {
        window.location.href = @json(route('account', 'account')) + '#address-tab';
      });
    }

    window.openCustomerLoginModal = openLoginModal;
    window.switchAuthModal = switchAuthModal;

    $(function() {
      var isGuest = {{ Auth::guard('customer')->check() ? 'false' : 'true' }};
      var hasLocation = {{ buyer_has_location() ? 'true' : 'false' }};
      var needsAddress = {{ customer_needs_delivery_address() ? 'true' : 'false' }};
      var forceLogin = {{ (request()->boolean('login') || ($errors->any() && ! Auth::guard('customer')->check())) ? 'true' : 'false' }};

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

      if (isGuest) {
        $('#loginModal, #createAccountModal, #passwordResetModal').on('shown.bs.modal', function() {
          $(this).find('.close').hide();
        });

        openLoginModal();
        return;
      }

      if (needsAddress && !hasLocation) {
        openAddressSetupModal();
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
