@if (config('services.google.place_api_key'))
  <script>
    window.initAddressAutocompleteApi = function() {
      window.addressAutocompleteApiReady = true;
      if (typeof window.initAdminMapPicker === 'function') {
        window.initAdminMapPicker();
      }
      document.dispatchEvent(new Event('addressAutocompleteApiReady'));
    };
  </script>
  <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&libraries=places&callback=initAddressAutocompleteApi"></script>
@endif

<style>
  .pac-container { z-index: 100000 !important; }
  .address-autocomplete-list {
    display: none;
    position: absolute;
    left: 0;
    right: 0;
    top: 100%;
    z-index: 100001;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
    max-height: 240px;
    overflow-y: auto;
    margin: 0;
    padding: 0;
    list-style: none;
  }
  .address-autocomplete-list li {
    padding: 10px 14px;
    cursor: pointer;
    font-size: 14px;
    border-bottom: 1px solid #f3f4f6;
  }
  .address-autocomplete-list li:last-child { border-bottom: 0; }
  .address-autocomplete-list li:hover,
  .address-autocomplete-list li.active { background: #eff6ff; }
  .address-autocomplete-list.is-open { display: block; }
  .address-autocomplete-wrap { position: relative; }
</style>

<script>
(function() {
  var searchUrl = @json(route('address.search'));
  var hasGoogleMaps = {{ config('services.google.place_api_key') ? 'true' : 'false' }};
  var initializedFields = new WeakSet();

  function findInForm(form, selectors) {
    for (var i = 0; i < selectors.length; i++) {
      var el = form.querySelector(selectors[i]);
      if (el) return el;
    }
    return null;
  }

  function setFieldValue(el, value) {
    if (!el || value == null || value === '') return;

    if (el.tagName === 'SELECT') {
      var matched = false;
      Array.prototype.forEach.call(el.options, function(opt) {
        if (matched) return;
        if (opt.text === value || opt.value === value || opt.text.indexOf(value) !== -1) {
          el.value = opt.value;
          matched = true;
        }
      });
      if (typeof jQuery !== 'undefined') {
        jQuery(el).trigger('change');
      }
      return;
    }

    el.value = value;
  }

  function fillAddressForm(form, data) {
    setFieldValue(findInForm(form, ['[name="address_line_1"]', '#address_line_1']), data.address_line_1 || data.label || '');
    setFieldValue(findInForm(form, ['[name="address_line_2"]', '#address_line_2']), data.address_line_2 || '');
    setFieldValue(findInForm(form, ['[name="city"]', '#city', '#address_city']), data.city || '');
    setFieldValue(findInForm(form, ['[name="zip_code"]', '#zip_code', '#postcode']), data.zip_code || data.postal_code || '');

    if (data.country) {
      setFieldValue(findInForm(form, ['[name="country_id"]', '#country_id', '#address_country']), data.country);
    }
    if (data.state) {
      setFieldValue(findInForm(form, ['[name="state_id"]', '#state_id', '#address_state']), data.state);
    }

    if (data.latitude != null && data.longitude != null) {
      setFieldValue(findInForm(form, ['[name="latitude"]', '#latitude']), data.latitude);
      setFieldValue(findInForm(form, ['[name="longitude"]', '#longitude']), data.longitude);

      if (window.marker && window.map && typeof google !== 'undefined') {
        var pos = { lat: parseFloat(data.latitude), lng: parseFloat(data.longitude) };
        window.marker.setPosition(pos);
        window.map.setCenter(pos);
      }
    }
  }

  function parseGoogleComponents(components) {
    var result = { address_line_1: '', zip_code: '', city: '', state: '', country: '' };

    components.forEach(function(component) {
      var type = component.types[0];
      switch (type) {
        case 'street_number':
          result.address_line_1 = component.long_name + ' ' + result.address_line_1;
          break;
        case 'route':
          result.address_line_1 = (result.address_line_1 + component.long_name).trim();
          break;
        case 'postal_code':
          result.zip_code = component.long_name;
          break;
        case 'locality':
        case 'postal_town':
          result.city = component.long_name;
          break;
        case 'administrative_area_level_1':
          result.state = component.short_name || component.long_name;
          break;
        case 'country':
          result.country = component.long_name;
          break;
      }
    });

    return result;
  }

  function attachGoogleAutocomplete(input, form) {
    if (!hasGoogleMaps || typeof google === 'undefined' || !google.maps || !google.maps.places) {
      return false;
    }

    var autocomplete = new google.maps.places.Autocomplete(input, {
      fields: ['address_components', 'geometry', 'formatted_address'],
      types: ['address'],
    });

    autocomplete.addListener('place_changed', function() {
      var place = autocomplete.getPlace();
      if (!place) return;

      var parsed = place.address_components ? parseGoogleComponents(place.address_components) : {};
      if (!parsed.address_line_1 && place.formatted_address) {
        parsed.address_line_1 = place.formatted_address.split(',')[0];
      }

      if (place.geometry && place.geometry.location) {
        parsed.latitude = place.geometry.location.lat();
        parsed.longitude = place.geometry.location.lng();
      }

      fillAddressForm(form, parsed);

      var line2 = findInForm(form, ['[name="address_line_2"]', '#address_line_2']);
      if (line2) line2.focus();
    });

    return true;
  }

  function attachServerAutocomplete(input, form) {
    var wrap = input.closest('.form-group') || input.parentNode;
    if (!wrap.classList.contains('address-autocomplete-wrap')) {
      wrap.classList.add('address-autocomplete-wrap');
    }

    var list = document.createElement('ul');
    list.className = 'address-autocomplete-list';
    wrap.appendChild(list);

    var timer = null;

    function hideList() {
      list.innerHTML = '';
      list.classList.remove('is-open');
    }

    input.setAttribute('autocomplete', 'off');

    input.addEventListener('input', function() {
      clearTimeout(timer);
      var query = input.value.trim();
      if (query.length < 3) {
        hideList();
        return;
      }

      timer = setTimeout(function() {
        fetch(searchUrl + '?query=' + encodeURIComponent(query), {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          list.innerHTML = '';
          (data.results || []).forEach(function(item, index) {
            var li = document.createElement('li');
            li.textContent = item.label;
            li.addEventListener('mousedown', function(e) {
              e.preventDefault();
              fillAddressForm(form, {
                address_line_1: item.label,
                latitude: item.latitude,
                longitude: item.longitude,
              });
              input.value = item.label;
              hideList();
            });
            list.appendChild(li);
          });
          if (list.children.length) {
            list.classList.add('is-open');
          } else {
            hideList();
          }
        })
        .catch(function() { hideList(); });
      }, 300);
    });

    input.addEventListener('blur', function() {
      setTimeout(hideList, 150);
    });

    return true;
  }

  function initAddressAutocomplete(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var fields = scope.querySelectorAll('[name="address_line_1"], #address_line_1');

    fields.forEach(function(input) {
      if (input.tagName !== 'INPUT' || initializedFields.has(input)) {
        return;
      }

      var form = input.closest('form') || input.closest('.modal-content') || scope;
      initializedFields.add(input);

      if (hasGoogleMaps && typeof google !== 'undefined' && google.maps && google.maps.places) {
        attachGoogleAutocomplete(input, form);
      } else {
        attachServerAutocomplete(input, form);
      }
    });
  }

  window.initAddressAutocomplete = initAddressAutocomplete;

  function boot() {
    initAddressAutocomplete(document);
  }

  if (hasGoogleMaps) {
    document.addEventListener('addressAutocompleteApiReady', boot);
    document.addEventListener('DOMContentLoaded', function() {
      setTimeout(function() {
        if (!window.addressAutocompleteApiReady) {
          boot();
        }
      }, 4000);
    });
  } else {
    document.addEventListener('DOMContentLoaded', boot);
  }

  if (typeof jQuery !== 'undefined') {
    jQuery(document).on('shown.bs.modal', function(e) {
      initAddressAutocomplete(e.target);
    });
  }
})();
</script>
