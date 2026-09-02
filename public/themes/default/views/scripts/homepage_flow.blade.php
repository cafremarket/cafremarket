{{-- Hyperlocal onboarding: homepage only — login first for guests, then location once --}}
<script>
  (function($) {
    if (typeof $ === 'undefined') {
      return;
    }

    var GUEST_CONTINUE_KEY = 'guest_continue';

    function cleanupModalOverlay() {
      $('.modal-backdrop').remove();
      $('body').removeClass('modal-open').css({ paddingRight: '', overflow: '' });
    }

    function hasStoredLocation() {
      if (!window.CafrepayLocationStorage) {
        return false;
      }
      var stored = window.CafrepayLocationStorage.read();
      return !!(stored && stored.latitude && stored.longitude);
    }

    function openLocationModal() {
      var $modal = $('#locationModal');
      if (!$modal.length) {
        return;
      }

      cleanupModalOverlay();

      $modal.modal({
        backdrop: true,
        keyboard: true,
        show: true
      });

      setTimeout(function() {
        if ($('.modal-backdrop').length && !$modal.hasClass('in')) {
          $modal.addClass('in').css('display', 'flex').attr('aria-hidden', 'false');
        }
      }, 400);
    }

    function openLoginModal() {
      var $login = $('#loginModal');
      if (!$login.length) {
        window.location.href = @json(route('homepage', ['login' => 1]));
        return;
      }

      cleanupModalOverlay();
      $login.modal('show');
    }

    window.openCustomerLoginModal = openLoginModal;

    $(function() {
      var isGuest = {{ Auth::guard('customer')->check() ? 'false' : 'true' }};
      var hasLocation = {{ buyer_has_location() ? 'true' : 'false' }};
      var isHomepage = {{ Request::routeIs('homepage') ? 'true' : 'false' }};
      var requireLocation = {{ config('hyperlocal.require_location_for_browse', true) ? 'true' : 'false' }};
      var forceLogin = {{ (request()->boolean('login') || ($errors->any() && ! Auth::guard('customer')->check())) ? 'true' : 'false' }};

      if (!isHomepage) {
        return;
      }

      if (!$('.modal.in').length) {
        cleanupModalOverlay();
      }

      function locationIsSet() {
        return hasLocation || hasStoredLocation();
      }

      function maybeShowLocation() {
        if (!requireLocation || locationIsSet()) {
          return;
        }
        openLocationModal();
      }

      function maybeShowLogin() {
        if (!isGuest) {
          maybeShowLocation();
          return;
        }

        if (forceLogin || !localStorage.getItem(GUEST_CONTINUE_KEY)) {
          openLoginModal();
          return;
        }

        maybeShowLocation();
      }

      function startOnboardingFlow() {
        if (!hasLocation && hasStoredLocation()) {
          return;
        }

        if (isGuest) {
          $('#loginModal').on('hidden.bs.modal', function() {
            cleanupModalOverlay();
            if (localStorage.getItem(GUEST_CONTINUE_KEY)) {
              setTimeout(maybeShowLocation, 300);
            }
          });

          $('#continueAsGuestBtn').on('click', function() {
            localStorage.setItem(GUEST_CONTINUE_KEY, '1');
            $('#loginModal').modal('hide');
            cleanupModalOverlay();
            setTimeout(maybeShowLocation, 350);
          });

          maybeShowLogin();
        } else {
          maybeShowLocation();
        }

        $('#locationModal').on('hidden.bs.modal', function() {
          cleanupModalOverlay();
        });
      }

      var restoreFn = window.CafrepayRestoreLocation || function() {
        return Promise.resolve(false);
      };

      restoreFn().then(function(synced) {
        if (synced) {
          return;
        }
        startOnboardingFlow();
      });
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
  #locationModal.modal.in {
    display: flex !important;
  }
  .delivery-location-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    background: rgba(255,255,255,0.15);
    color: inherit;
    text-decoration: none;
    font-size: 13px;
    transition: background 0.2s;
  }
  .delivery-location-chip:hover {
    background: rgba(255,255,255,0.25);
    color: inherit;
    text-decoration: none;
  }
  .delivery-location-chip--empty {
    background: rgba(255,193,7,0.2);
  }
  .nearby-store-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border-radius: 12px;
    padding: 20px 16px;
  }
  .nearby-store-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
  }
  .nearby-featured-section,
  .nearby-stores-section {
    padding: 40px 0 20px;
  }
  .hyperlocal-location-gate {
    text-align: center;
    padding: 60px 20px;
  }
</style>
