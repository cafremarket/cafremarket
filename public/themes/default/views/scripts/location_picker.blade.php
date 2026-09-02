@if (config('services.google.place_api_key'))
  <script>
    window.initLocationMapServices = function() {
      window.locationMapApiReady = true;
      document.dispatchEvent(new Event('locationMapApiReady'));
    };
  </script>
  <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&libraries=places&callback=initLocationMapServices"></script>
@else
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endif

<script>
(function() {
  var saveUrl = '{{ route('customer.location.save') }}';
  var reverseGeocodeUrl = '{{ route('customer.location.reverse') }}';
  var searchAddressUrl = '{{ route('customer.location.search') }}';
  var csrfToken = '{{ csrf_token() }}';
  var hasGoogleMaps = {{ config('services.google.place_api_key') ? 'true' : 'false' }};
  var defaultLat = {{ session('buyer_latitude') ?: '-25.9655' }};
  var defaultLng = {{ session('buyer_longitude') ?: '32.5832' }};
  var useCurrentLocationLabel = @json('<i class="fal fa-crosshairs"></i> ' . trans('theme.use_current_location'));
  var fetchingAddressLabel = @json(trans('theme.fetching_address'));
  var addressLookupFailedLabel = @json(trans('theme.address_lookup_failed'));
  var confirmLocationLabel = @json(trans('theme.confirm_location'));
  var LOCATION_STORAGE_KEY = 'cafrepay_buyer_location';
  var LOCATION_SYNC_FLAG = 'cafrepay_location_synced';
  var hasServerLocation = {{ buyer_has_location() ? 'true' : 'false' }};

  window.CafrepayLocationStorage = window.CafrepayLocationStorage || {
    key: LOCATION_STORAGE_KEY,
    read: function() {
      try {
        var raw = localStorage.getItem(LOCATION_STORAGE_KEY);
        return raw ? JSON.parse(raw) : null;
      } catch (e) {
        return null;
      }
    },
    write: function(data) {
      if (!data || !data.latitude || !data.longitude) {
        return;
      }
      localStorage.setItem(LOCATION_STORAGE_KEY, JSON.stringify({
        latitude: parseFloat(data.latitude),
        longitude: parseFloat(data.longitude),
        address_text: data.address_text || ''
      }));
    }
  };

  function restoreLocationFromLocalStorage() {
    if (hasServerLocation) {
      return Promise.resolve(false);
    }

    var stored = window.CafrepayLocationStorage.read();
    if (!stored || !stored.latitude || !stored.longitude) {
      return Promise.resolve(false);
    }

    return fetch(saveUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(stored)
    })
    .then(function(response) {
      return response.ok;
    })
    .catch(function() {
      return false;
    });
  }

  window.CafrepayRestoreLocation = restoreLocationFromLocalStorage;

  var mapInstance = null;
  var mapMarker = null;
  var placesAutocomplete = null;
  var addressLookupToken = 0;
  var searchDebounceTimer = null;

  function looksLikeCoordinates(value) {
    return /^-?\d+(\.\d+)?,\s*-?\d+(\.\d+)?$/.test(String(value || '').trim());
  }

  function setPreviewMessage(message, isError) {
    var preview = document.getElementById('locationPreview');
    var previewText = document.getElementById('locationPreviewText');
    preview.classList.remove('d-none');
    preview.classList.toggle('alert-danger', !!isError);
    preview.classList.toggle('alert-light', !isError);
    previewText.textContent = message;
  }

  function hidePreview() {
    document.getElementById('locationPreview').classList.add('d-none');
  }

  function setSaveEnabled(enabled) {
    document.getElementById('saveLocationBtn').disabled = !enabled;
  }

  function updateMapPosition(lat, lng, zoomTo) {
    if (!mapInstance || !mapMarker) {
      return;
    }

    if (hasGoogleMaps) {
      var position = { lat: parseFloat(lat), lng: parseFloat(lng) };
      mapMarker.setPosition(position);
      if (zoomTo) {
        mapInstance.setCenter(position);
        mapInstance.setZoom(16);
      } else {
        mapInstance.panTo(position);
      }
      return;
    }

    mapMarker.setLatLng([lat, lng]);
    if (zoomTo) {
      mapInstance.setView([lat, lng], 16);
    } else {
      mapInstance.panTo([lat, lng]);
    }
  }

  function applyResolvedLocation(lat, lng, address) {
    document.getElementById('locationLatitude').value = lat;
    document.getElementById('locationLongitude').value = lng;
    document.getElementById('locationAddressText').value = address;

    var searchInput = document.getElementById('locationSearch');
    if (searchInput) {
      searchInput.value = address;
    }

    setPreviewMessage(address, false);
    setSaveEnabled(true);
    updateMapPosition(lat, lng);
    hideAutocompleteList();
  }

  function hideAutocompleteList() {
    var list = document.getElementById('locationAutocompleteList');
    if (list) {
      list.innerHTML = '';
      list.classList.add('d-none');
    }
  }

  function fetchAddressFromServer(lat, lng) {
    return fetch(reverseGeocodeUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        latitude: parseFloat(lat),
        longitude: parseFloat(lng)
      })
    })
    .then(function(r) {
      if (!r.ok) {
        return null;
      }
      return r.json();
    })
    .then(function(data) { return (data && data.address_text) ? data.address_text : null; })
    .catch(function() { return null; });
  }

  function resolveAddressFromCoords(lat, lng) {
    return fetchAddressFromServer(lat, lng).then(function(serverAddress) {
      if (serverAddress) {
        return serverAddress;
      }

      if (hasGoogleMaps && typeof google !== 'undefined' && google.maps && google.maps.Geocoder) {
        return new Promise(function(resolve) {
          var geocoder = new google.maps.Geocoder();
          geocoder.geocode({ location: { lat: parseFloat(lat), lng: parseFloat(lng) } }, function(results, status) {
            if (status === 'OK' && results && results[0] && results[0].formatted_address) {
              resolve(results[0].formatted_address);
              return;
            }

            resolve(null);
          });
        });
      }

      return null;
    });
  }

  function updateLocationFromCoords(lat, lng, knownAddress, zoomTo) {
    var token = ++addressLookupToken;

    document.getElementById('locationLatitude').value = lat;
    document.getElementById('locationLongitude').value = lng;
    setSaveEnabled(false);
    setPreviewMessage(fetchingAddressLabel, false);
    updateMapPosition(lat, lng, !!zoomTo);

    if (knownAddress && !looksLikeCoordinates(knownAddress)) {
      applyResolvedLocation(lat, lng, knownAddress);
      return Promise.resolve(knownAddress);
    }

    return resolveAddressFromCoords(lat, lng).then(function(address) {
      if (token !== addressLookupToken) {
        return null;
      }

      if (address && !looksLikeCoordinates(address)) {
        applyResolvedLocation(lat, lng, address);
        return address;
      }

      document.getElementById('locationAddressText').value = '';
      setPreviewMessage(addressLookupFailedLabel, true);
      setSaveEnabled(false);
      return null;
    });
  }

  function renderAutocompleteResults(results) {
    var list = document.getElementById('locationAutocompleteList');
    if (!list) {
      return;
    }

    list.innerHTML = '';

    if (!results.length) {
      list.classList.add('d-none');
      return;
    }

    results.forEach(function(item) {
      var li = document.createElement('li');
      li.textContent = item.label;
      li.addEventListener('click', function() {
        updateLocationFromCoords(item.latitude, item.longitude, item.label, true);
      });
      list.appendChild(li);
    });

    list.classList.remove('d-none');
  }

  function searchAddresses(query) {
    return fetch(searchAddressUrl + '?query=' + encodeURIComponent(query), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) { return data.results || []; })
    .catch(function() { return []; });
  }

  function initServerAutocomplete() {
    var input = document.getElementById('locationSearch');
    if (!input || input.dataset.serverAutocompleteBound === '1') {
      return;
    }

    input.dataset.serverAutocompleteBound = '1';

    input.addEventListener('input', function() {
      clearTimeout(searchDebounceTimer);
      var query = input.value.trim();

      if (query.length < 3) {
        hideAutocompleteList();
        return;
      }

      searchDebounceTimer = setTimeout(function() {
        searchAddresses(query).then(renderAutocompleteResults);
      }, 350);
    });

    input.addEventListener('blur', function() {
      setTimeout(hideAutocompleteList, 180);
    });
  }

  function initGooglePlacesAutocomplete() {
    if (!hasGoogleMaps || placesAutocomplete) {
      return;
    }

    var input = document.getElementById('locationSearch');
    if (!input || typeof google === 'undefined' || !google.maps || !google.maps.places) {
      return;
    }

    placesAutocomplete = new google.maps.places.Autocomplete(input, {
      fields: ['formatted_address', 'geometry', 'name'],
      types: ['geocode']
    });

    if (mapInstance && mapInstance.getBounds) {
      placesAutocomplete.bindTo('bounds', mapInstance);
    }

    placesAutocomplete.addListener('place_changed', function() {
      var place = placesAutocomplete.getPlace();
      if (place.geometry && place.geometry.location) {
        updateLocationFromCoords(
          place.geometry.location.lat(),
          place.geometry.location.lng(),
          place.formatted_address || place.name,
          true
        );
      }
    });
  }

  function initGoogleFallbackAutocomplete() {
    var input = document.getElementById('locationSearch');
    if (!input) {
      return;
    }

    input.addEventListener('keydown', function(event) {
      var list = document.getElementById('locationAutocompleteList');
      if (!list || list.classList.contains('d-none')) {
        return;
      }

      if (event.key === 'Escape') {
        hideAutocompleteList();
      }
    });
  }

  function initAutocomplete() {
    initServerAutocomplete();

    if (hasGoogleMaps && window.locationMapApiReady) {
      initGooglePlacesAutocomplete();
    }

    initGoogleFallbackAutocomplete();
  }

  function initGoogleMap() {
    var canvas = document.getElementById('locationMapCanvas');
    if (!canvas || typeof google === 'undefined' || !google.maps) {
      return false;
    }

    var lat = parseFloat(document.getElementById('locationLatitude').value) || defaultLat;
    var lng = parseFloat(document.getElementById('locationLongitude').value) || defaultLng;
    var center = { lat: lat, lng: lng };

    mapInstance = new google.maps.Map(canvas, {
      center: center,
      zoom: 15,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: false
    });

    mapMarker = new google.maps.Marker({
      position: center,
      map: mapInstance,
      draggable: true
    });

    mapMarker.addListener('dragend', function() {
      var pos = mapMarker.getPosition();
      updateLocationFromCoords(pos.lat(), pos.lng());
    });

    mapInstance.addListener('click', function(event) {
      mapMarker.setPosition(event.latLng);
      updateLocationFromCoords(event.latLng.lat(), event.latLng.lng());
    });

    if (placesAutocomplete) {
      placesAutocomplete.bindTo('bounds', mapInstance);
    } else {
      initGooglePlacesAutocomplete();
    }

    return true;
  }

  function initLeafletMap() {
    var canvas = document.getElementById('locationMapCanvas');
    if (!canvas || typeof L === 'undefined') {
      return false;
    }

    var lat = parseFloat(document.getElementById('locationLatitude').value) || defaultLat;
    var lng = parseFloat(document.getElementById('locationLongitude').value) || defaultLng;

    mapInstance = L.map(canvas, { zoomControl: true }).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(mapInstance);

    mapMarker = L.marker([lat, lng], { draggable: true }).addTo(mapInstance);

    mapMarker.on('dragend', function(event) {
      var pos = event.target.getLatLng();
      updateLocationFromCoords(pos.lat, pos.lng);
    });

    mapInstance.on('click', function(event) {
      mapMarker.setLatLng(event.latlng);
      updateLocationFromCoords(event.latlng.lat, event.latlng.lng);
    });

    setTimeout(function() {
      mapInstance.invalidateSize();
    }, 200);

    return true;
  }

  function initLocationMap() {
    if (mapInstance) {
      if (hasGoogleMaps && typeof google !== 'undefined') {
        google.maps.event.trigger(mapInstance, 'resize');
        var lat = parseFloat(document.getElementById('locationLatitude').value) || defaultLat;
        var lng = parseFloat(document.getElementById('locationLongitude').value) || defaultLng;
        mapInstance.setCenter({ lat: lat, lng: lng });
      } else if (!hasGoogleMaps && mapInstance.invalidateSize) {
        mapInstance.invalidateSize();
      }
      return;
    }

    if (hasGoogleMaps) {
      initGoogleMap();
    } else {
      initLeafletMap();
    }
  }

  function setCurrentLocationLoading(loading) {
    var mapBtn = document.getElementById('mapCurrentLocationBtn');
    var useBtn = document.getElementById('useCurrentLocationBtn');

    [mapBtn, useBtn].forEach(function(btn) {
      if (!btn) {
        return;
      }

      btn.disabled = !!loading;
    });

    if (mapBtn) {
      mapBtn.innerHTML = loading
        ? '<i class="fal fa-spinner fa-spin"></i>'
        : '<i class="fal fa-crosshairs"></i>';
    }

    if (useBtn) {
      useBtn.innerHTML = loading
        ? '<i class="fal fa-spinner fa-spin"></i> ' + fetchingAddressLabel
        : useCurrentLocationLabel;
    }
  }

  function fetchCurrentLocation() {
    if (!navigator.geolocation) {
      alert('{{ trans('theme.geolocation_not_supported') }}');
      return;
    }

    setCurrentLocationLoading(true);

    navigator.geolocation.getCurrentPosition(function(pos) {
      updateLocationFromCoords(pos.coords.latitude, pos.coords.longitude, null, true).finally(function() {
        setCurrentLocationLoading(false);
      });
    }, function() {
      setCurrentLocationLoading(false);
      alert('{{ trans('theme.geolocation_denied') }}');
    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
  }

  function saveLocation() {
    var lat = document.getElementById('locationLatitude').value;
    var lng = document.getElementById('locationLongitude').value;
    var address = document.getElementById('locationAddressText').value;

    if (!lat || !lng) {
      return;
    }

    var btn = document.getElementById('saveLocationBtn');

    function submitSave(resolvedAddress) {
      btn.disabled = true;
      btn.textContent = '{{ trans('theme.saving') }}...';

      fetch(saveUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          latitude: parseFloat(lat),
          longitude: parseFloat(lng),
          address_text: resolvedAddress
        })
      })
      .then(function(r) {
        if (!r.ok) {
          return r.json().then(function(data) {
            throw new Error(data.message || '{{ trans('theme.location_save_failed') }}');
          });
        }
        return r.json();
      })
      .then(function() {
        if (window.CafrepayLocationStorage) {
          window.CafrepayLocationStorage.write({
            latitude: lat,
            longitude: lng,
            address_text: resolvedAddress
          });
        }
        sessionStorage.setItem('location_just_saved', '1');
        if (typeof toastr !== 'undefined') {
          toastr.success('{{ trans('theme.location_saved') }}');
        }
        $('#locationModal').modal('hide');
        window.location.reload();
      })
      .catch(function(error) {
        btn.disabled = false;
        btn.textContent = confirmLocationLabel;
        alert(error.message || '{{ trans('theme.location_save_failed') }}');
      });
    }

    if (address && !looksLikeCoordinates(address)) {
      submitSave(address);
      return;
    }

    btn.disabled = true;
    btn.textContent = fetchingAddressLabel;

    updateLocationFromCoords(lat, lng).then(function(resolvedAddress) {
      if (resolvedAddress) {
        submitSave(resolvedAddress);
        return;
      }

      btn.disabled = true;
      btn.textContent = confirmLocationLabel;
    });
  }

  function bootLocationPicker() {
    var form = document.getElementById('locationForm');
    if (!form) {
      return;
    }

    form.addEventListener('submit', function(e) {
      e.preventDefault();
      saveLocation();
    });

    document.getElementById('useCurrentLocationBtn').addEventListener('click', fetchCurrentLocation);
    document.getElementById('mapCurrentLocationBtn').addEventListener('click', fetchCurrentLocation);

    document.addEventListener('click', function(event) {
      var wrap = document.querySelector('.location-search-wrap');
      if (wrap && !wrap.contains(event.target)) {
        hideAutocompleteList();
      }
    });

    if (hasGoogleMaps && window.locationMapApiReady) {
      initAutocomplete();
    } else if (!hasGoogleMaps) {
      initAutocomplete();
    } else {
      document.addEventListener('locationMapApiReady', function() {
        initAutocomplete();
      }, { once: true });
    }

    var savedAddress = document.getElementById('locationAddressText').value;
    var savedLat = document.getElementById('locationLatitude').value;
    var savedLng = document.getElementById('locationLongitude').value;

    if (savedAddress && !looksLikeCoordinates(savedAddress)) {
      setSaveEnabled(true);
    } else if (savedLat && savedLng) {
      updateLocationFromCoords(savedLat, savedLng);
    } else {
      hidePreview();
      setSaveEnabled(false);
    }

    $('#locationModal').on('show.bs.modal shown.bs.modal', function() {
      $(this).css('display', 'flex');
    });

    $('#locationModal').on('shown.bs.modal', function() {
      if (hasGoogleMaps && !window.locationMapApiReady) {
        document.addEventListener('locationMapApiReady', function() {
          initLocationMap();
          initAutocomplete();
        }, { once: true });
      } else {
        initLocationMap();
        initAutocomplete();
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    bootLocationPicker();
  });
})();
</script>
