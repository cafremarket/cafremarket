<script>
  $(document).ready(function() {
    let popup_delay = {{ get_popup_data()['delay'] ?? 2000 }};
    if (getCookie('zcart_dynamic_popup') !== "hide") {
      setTimeout(function() {
        $("#zcart-popup-modal").modal('show');
        $('#zcart-popup-modal').css('display', 'flex');
      }, popup_delay);
    }

    const expire_at = new Date(Date.now() + (24 * 60 * 60 * 1000)); // Set +24hrs

    $('#popup-newslette-form').submit(function(e) {
      document.cookie = `zcart_dynamic_popup=hide; expires=${expire_at.toUTCString()}`;
    });

    $(document).on('change', '#js-hide-newsletter-check', function(e) {
      document.cookie = `zcart_dynamic_popup=hide; expires=${expire_at.toUTCString()}`;
    });

    function getCookie(cookieName) {
      let cookie = {};
      document.cookie.split(';').forEach(function(el) {
        let [key, value] = el.split('=');

        cookie[key.trim()] = value;
      })

      return cookie[cookieName];
    }
  });
</script>
