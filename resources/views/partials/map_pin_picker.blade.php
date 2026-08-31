{{-- Google Maps pin picker — requires latitude/longitude hidden inputs and GOOGLE_PLACE_KEY --}}
@if (config('services.google.place_api_key'))
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

<script>
(function() {
  var defaultLat = parseFloat(document.getElementById('latitude').value) || -25.9655;
  var defaultLng = parseFloat(document.getElementById('longitude').value) || 32.5832;
  var reverseUrl = @json(route('address.reverse'));
  var csrfToken = @json(csrf_token());
  var fetchingLabel = @json(trans('theme.fetching_address'));
  var useLocationLabel = @json(trans('theme.use_current_location'));
  var geoUnsupportedLabel = @json(trans('theme.geolocation_not_supported'));
  var geoDeniedLabel = @json(trans('theme.geolocation_denied'));

  function setCoords(lat, lng) {
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
  }

  function findAddressForm() {
    var wrap = document.getElementById('map-picker-wrap');
    return wrap ? wrap.closest('form') : document.querySelector('form');
  }

  function fillAddressFields(addressText) {
    if (!addressText) return;

    var form = findAddressForm();
    if (!form) return;

    var line1 = form.querySelector('[name="address_line_1"]');
    var city = form.querySelector('[name="city"]');

    if (line1) {
      line1.value = addressText;
    }

    if (city && !city.value) {
      var parts = addressText.split(',').map(function(p) { return p.trim(); }).filter(Boolean);
      if (parts.length >= 2) {
        city.value = parts[parts.length - 2] || parts[1] || '';
      }
    }
  }

  function reverseGeocodeAndFill(lat, lng) {
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
      if (data.address_text) {
        fillAddressFields(data.address_text);
      }
    })
    .catch(function() {});
  }

  window.updateAdminMapFromCoords = function(lat, lng, pan) {
    setCoords(lat, lng);

    if (window.marker && window.map) {
      var pos = { lat: parseFloat(lat), lng: parseFloat(lng) };
      window.marker.setPosition(pos);

      if (pan) {
        window.map.setCenter(pos);
        window.map.setZoom(16);
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
    });

    window.map.addListener('click', function(e) {
      window.marker.setPosition(e.latLng);
      setCoords(e.latLng.lat(), e.latLng.lng());
    });

    if (!document.getElementById('latitude').value) {
      setCoords(defaultLat, defaultLng);
    }
  };

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
})();
</script>
@if (empty($skipMapsScript))
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&callback=initAdminMapPicker"></script>
@endif
@endif
