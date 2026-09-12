{{-- Address pin picker — draggable/click-to-pin map with a "use current location" button.
     Uses Google Maps when a key is configured, otherwise falls back to a free
     OpenStreetMap/Leaflet map so the picker always renders, key or no key. --}}
@php
  $googleMapsKey = config('services.google.place_api_key');
@endphp
<style>
  .map-picker-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
  }
  .map-current-location-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0 18px;
    border: 1px solid #bfdbfe;
    border-radius: 999px;
    background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
    color: #1d4ed8;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(37, 99, 235, 0.12);
    transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
  }
  .map-current-location-btn:hover,
  .map-current-location-btn:focus {
    background: linear-gradient(180deg, #dbeafe 0%, #bfdbfe 100%);
    border-color: #93c5fd;
    color: #1e40af;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.18);
    outline: none;
    transform: translateY(-1px);
  }
  .map-current-location-btn:disabled {
    opacity: 0.72;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
  }
  .map-location-status.is-success { color: #059669; font-weight: 600; }
</style>
<div class="form-group admin-map-picker" id="map-picker-wrap">
  <label>{{ trans('app.store_location') }}</label>
  <p class="help-block text-muted">{{ trans('help.drag_pin_to_set_location') }}</p>

  <div class="map-picker-toolbar">
    <button type="button" id="map-use-current-location" class="map-current-location-btn">
      <i class="fa fa-crosshairs"></i> {{ trans('theme.use_current_location') }}
    </button>
    <span id="map-location-status" class="map-location-status"></span>
  </div>

  <div id="admin-map-canvas" style="height: 320px; width: 100%; border-radius: 8px; border: 1px solid #e5e7eb;"></div>
  <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $latitude ?? '') }}">
  <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $longitude ?? '') }}">
</div>

@if (! $googleMapsKey)
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endif

<script>
(function() {
  var hasGoogleMaps = {{ $googleMapsKey ? 'true' : 'false' }};
  var defaultLat = parseFloat(document.getElementById('latitude').value) || -25.9655;
  var defaultLng = parseFloat(document.getElementById('longitude').value) || 32.5832;
  var hasExistingCoords = !!(document.getElementById('latitude').value && document.getElementById('longitude').value);
  var reverseUrl = @json(route('address.reverse'));

  // Always guarantee a coordinate value up front, synchronously, regardless of
  // whether the map library below ever finishes loading. Coordinates are a
  // required field on the address form, and when this partial is injected
  // into the admin ajax modal (jQuery .html()), a dynamically-inserted
  // <script src> for Leaflet loads asynchronously — the map/marker may not
  // exist yet by the time this script runs, but the hidden inputs must never
  // be left empty or the form silently fails server-side validation.
  if (!document.getElementById('latitude').value) {
    document.getElementById('latitude').value = defaultLat;
    document.getElementById('longitude').value = defaultLng;
  }
  var csrfToken = @json(csrf_token());
  var fetchingLabel = @json(trans('theme.fetching_address'));
  var useLocationLabel = @json(trans('theme.use_current_location'));
  var geoUnsupportedLabel = @json(trans('theme.geolocation_not_supported'));
  var geoDeniedLabel = @json(trans('theme.geolocation_denied'));
  var statesUrl = @json(route('ajax.getCountryStates'));

  function setCoords(lat, lng) {
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
  }

  function findAddressForm() {
    var wrap = document.getElementById('map-picker-wrap');
    return wrap ? wrap.closest('form') : document.querySelector('form');
  }

  function setSelectByText(select, text) {
    if (!select || !text) return false;
    var matched = false;
    Array.prototype.forEach.call(select.options, function(opt) {
      if (matched || !opt.value) return;
      if (opt.text.trim().toLowerCase() === String(text).trim().toLowerCase()) {
        select.value = opt.value;
        matched = true;
      }
    });
    if (matched && typeof jQuery !== 'undefined') {
      jQuery(select).trigger('change');
    }
    return matched;
  }

  function fillAddressFields(details) {
    var form = findAddressForm();
    if (!form || !details) return;

    var line1 = form.querySelector('[name="address_line_1"]');
    var city = form.querySelector('[name="city"]');
    var zip = form.querySelector('[name="zip_code"]');
    var country = form.querySelector('[name="country_id"]');
    var state = form.querySelector('[name="state_id"]');

    if (line1 && !line1.value && details.address_line_1) {
      line1.value = details.address_line_1;
    }
    if (city && !city.value && details.city) {
      city.value = details.city;
    }
    if (zip && !zip.value && details.zip_code) {
      zip.value = details.zip_code;
    }

    var countryMatched = country && details.country ? setSelectByText(country, details.country) : false;

    if (state && details.state) {
      if (countryMatched && country) {
        fetch(statesUrl + '?id=' + encodeURIComponent(country.value), {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
          .then(function(r) { return r.ok ? r.json() : {}; })
          .then(function(states) {
            var html = state.options[0] ? state.options[0].outerHTML : '';
            Object.keys(states || {}).forEach(function(id) {
              html += '<option value="' + id + '">' + states[id] + '</option>';
            });
            state.innerHTML = html;
            setSelectByText(state, details.state);
          })
          .catch(function() {});
      } else {
        setSelectByText(state, details.state);
      }
    }
  }

  function reverseGeocodeAndFill(lat, lng, pan) {
    return fetch(reverseUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        latitude: parseFloat(lat),
        longitude: parseFloat(lng),
      }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data && data.details) {
        fillAddressFields(data.details);
      } else if (data && data.address_text) {
        fillAddressFields({ address_line_1: data.address_text });
      }
    })
    .catch(function() {});
  }

  var leafletMap = null;
  var leafletMarker = null;

  window.updateAdminMapFromCoords = function(lat, lng, pan) {
    setCoords(lat, lng);

    if (hasGoogleMaps && window.marker && window.map) {
      var pos = { lat: parseFloat(lat), lng: parseFloat(lng) };
      window.marker.setPosition(pos);

      if (pan) {
        window.map.setCenter(pos);
        window.map.setZoom(16);
      }
      return;
    }

    if (leafletMap && leafletMarker) {
      leafletMarker.setLatLng([lat, lng]);

      if (pan) {
        leafletMap.setView([lat, lng], 16);
      }
    }
  };

  window.initAdminMapPicker = function() {
    var el = document.getElementById('admin-map-canvas');
    if (!el || typeof google === 'undefined') return;

    window.map = new google.maps.Map(el, {
      center: { lat: defaultLat, lng: defaultLng },
      zoom: 15,
    });

    window.marker = new google.maps.Marker({
      position: { lat: defaultLat, lng: defaultLng },
      map: window.map,
      draggable: true,
    });

    window.marker.addListener('dragend', function() {
      var pos = window.marker.getPosition();
      setCoords(pos.lat(), pos.lng());
      reverseGeocodeAndFill(pos.lat(), pos.lng());
    });

    window.map.addListener('click', function(e) {
      window.marker.setPosition(e.latLng);
      setCoords(e.latLng.lat(), e.latLng.lng());
      reverseGeocodeAndFill(e.latLng.lat(), e.latLng.lng());
    });

    if (!document.getElementById('latitude').value) {
      setCoords(defaultLat, defaultLng);
    }
  };

  function initLeafletPicker() {
    var el = document.getElementById('admin-map-canvas');
    if (!el || typeof L === 'undefined' || leafletMap) return;

    leafletMap = L.map(el).setView([defaultLat, defaultLng], hasExistingCoords ? 16 : 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap',
    }).addTo(leafletMap);

    leafletMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(leafletMap);

    leafletMarker.on('dragend', function() {
      var pos = leafletMarker.getLatLng();
      setCoords(pos.lat, pos.lng);
      reverseGeocodeAndFill(pos.lat, pos.lng);
    });

    leafletMap.on('click', function(e) {
      leafletMarker.setLatLng(e.latlng);
      setCoords(e.latlng.lat, e.latlng.lng);
      reverseGeocodeAndFill(e.latlng.lat, e.latlng.lng);
    });

    if (!document.getElementById('latitude').value) {
      setCoords(defaultLat, defaultLng);
    }

    setTimeout(function() { leafletMap.invalidateSize(); }, 150);
  }

  function setCurrentLocationLoading(loading) {
    var btn = document.getElementById('map-use-current-location');
    var status = document.getElementById('map-location-status');

    if (!btn) return;

    btn.disabled = !!loading;
    btn.innerHTML = loading
      ? '<i class="fa fa-spinner fa-spin"></i> ' + fetchingLabel
      : '<i class="fa fa-crosshairs"></i> ' + useLocationLabel;

    if (status) {
      status.textContent = loading ? fetchingLabel : '';
    }
  }

  function useCurrentLocation() {
    if (!navigator.geolocation) {
      alert(geoUnsupportedLabel);
      return;
    }

    setCurrentLocationLoading(true);

    navigator.geolocation.getCurrentPosition(function(pos) {
      var lat = pos.coords.latitude;
      var lng = pos.coords.longitude;

      window.updateAdminMapFromCoords(lat, lng, true);

      reverseGeocodeAndFill(lat, lng).finally(function() {
        setCurrentLocationLoading(false);
        var status = document.getElementById('map-location-status');
        if (status) {
          status.textContent = @json(trans('app.store_location_set'));
          status.classList.add('is-success');
        }
      });
    }, function() {
      setCurrentLocationLoading(false);
      alert(geoDeniedLabel);
    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
  }

  var currentLocationBtn = document.getElementById('map-use-current-location');
  if (currentLocationBtn) {
    currentLocationBtn.addEventListener('click', useCurrentLocation);
  }

  window.useAdminMapCurrentLocation = useCurrentLocation;

  if (hasGoogleMaps) {
    if (typeof google !== 'undefined' && google.maps) {
      window.initAdminMapPicker();
    }
    // else: waits for the Google Maps script `callback=initAdminMapPicker` to fire.
  } else if (typeof L !== 'undefined') {
    // Leaflet is already loaded on the page (e.g. a wizard rendered earlier
    // in this same page already pulled it in) — safe to init immediately.
    initLeafletPicker();
  } else {
    // Load Leaflet ourselves and only touch the map once it has actually
    // finished loading — this partial is frequently injected into the admin
    // ajax modal via jQuery's `.html()`, which loads a dynamically-inserted
    // <script src> asynchronously, so we can't rely on script order/readyState.
    var existing = document.querySelector('script[data-leaflet-loader]');
    if (existing) {
      existing.addEventListener('load', initLeafletPicker);
    } else {
      var leafletScript = document.createElement('script');
      leafletScript.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
      leafletScript.crossOrigin = '';
      leafletScript.setAttribute('data-leaflet-loader', '1');
      leafletScript.onload = initLeafletPicker;
      document.head.appendChild(leafletScript);
    }
  }
})();
</script>
@if ($googleMapsKey && empty($skipMapsScript))
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&callback=initAdminMapPicker"></script>
@endif
