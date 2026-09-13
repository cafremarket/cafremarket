<script>
(function() {
  var $modal = $('#dynamicPopupModal');
  if (!$modal.length) return;

  var popupId = $modal.data('popup-id');
  var frequency = $modal.data('popup-frequency');
  var delay = parseInt($modal.data('popup-delay'), 10) || 0;

  var storageKey = 'popup_seen_' + popupId;

  function hasBeenSeen() {
    try {
      if (frequency === 'every_page_load') return false;

      if (frequency === 'once_per_session') {
        return sessionStorage.getItem(storageKey) === '1';
      }

      if (frequency === 'once_per_day') {
        var last = parseInt(localStorage.getItem(storageKey), 10);
        return !isNaN(last) && (Date.now() - last) < 24 * 60 * 60 * 1000;
      }

      // once_only
      return localStorage.getItem(storageKey) === '1';
    } catch (e) {
      return false;
    }
  }

  function markSeen() {
    try {
      if (frequency === 'once_per_session') {
        sessionStorage.setItem(storageKey, '1');
      } else if (frequency === 'once_per_day') {
        localStorage.setItem(storageKey, String(Date.now()));
      } else if (frequency === 'once_only') {
        localStorage.setItem(storageKey, '1');
      }
    } catch (e) {}
  }

  if (hasBeenSeen()) return;

  setTimeout(function() {
    $modal.modal('show');
  }, delay);

  $modal.on('hide.bs.modal', markSeen);
})();
</script>
