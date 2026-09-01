@php
  $googleMapsKey = $googleMapsKey ?? google_maps_api_key();
@endphp
@if ($googleMapsKey)
  <script>
    if (typeof window.initLocationMapServices !== 'function') {
      window.initLocationMapServices = function() {
        window.locationMapApiReady = true;
        document.dispatchEvent(new Event('locationMapApiReady'));
      };
    }
  </script>
  @unless (! empty($skipMapsScript))
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&libraries=places&callback=initLocationMapServices"></script>
  @endunless
@else
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@endif

<script>
(function() {
  var wizardId = @json($wizardId ?? 'addr-wizard');
  var reverseGeocodeUrl = @json(route('address.reverse'));
  var searchAddressUrl = @json(route('address.search'));
  var statesUrl = @json(route('ajax.getCountryStates'));
  var csrfToken = @json(csrf_token());
  var hasGoogleMaps = {{ $googleMapsKey ? 'true' : 'false' }};
  var deferInit = {{ ! empty($deferInit) ? 'true' : 'false' }};
  var defaultLat = parseFloat(@json($initialLat ?? '-25.9655'));
  var defaultLng = parseFloat(@json($initialLng ?? '32.5832'));
  var hasExistingCoords = @json(! empty($hasExistingCoords) && $hasExistingCoords === 'true');
  var startAtStep = parseInt(@json($startAtStep ?? 1), 10);
  var initialLocationText = @json($initialLocationText ?? '');
  var fetchingLabel = @json(trans('theme.fetching_address'));
  var lookupFailedLabel = @json(trans('theme.address_lookup_failed'));
  var geoUnsupportedLabel = @json(trans('theme.geolocation_not_supported'));
  var geoDeniedLabel = @json(trans('theme.geolocation_denied'));
  var statePlaceholder = @json(trans('theme.placeholder.state'));
  var hiddenClass = @json($hiddenClass ?? 'd-none');
  var previewErrorClass = hiddenClass === 'is-hidden' ? 'is-error' : 'alert-danger';
  var previewOkClass = hiddenClass === 'is-hidden' ? '' : 'alert-light';

  function hideElement(el) {
    if (el) el.classList.add(hiddenClass);
  }

  function showElement(el) {
    if (el) el.classList.remove(hiddenClass);
  }

  function setStep(step) {
    qa('.address-wizard-step').forEach(function(el) {
      el.classList.toggle('is-active', el.getAttribute('data-step') === String(step));
    });
    qa('.address-wizard-panel').forEach(function(el) {
      var isActive = el.getAttribute('data-panel') === String(step);
      el.classList.toggle(hiddenClass, !isActive);
    });
  }

  function getRoot() {
    return document.getElementById(wizardId);
  }

  function q(sel) {
    var root = getRoot();
    return root ? root.querySelector(sel) : null;
  }

  function qa(sel) {
    var root = getRoot();
    return root ? root.querySelectorAll(sel) : [];
  }

  var mapInstance = null;
  var mapMarker = null;
  var lookupToken = 0;
  var searchTimer = null;
  var resolvedAddress = initialLocationText || '';

  function setLatLng(lat, lng) {
    var latEl = q('.addr-wizard-lat');
    var lngEl = q('.addr-wizard-lng');
    if (latEl) latEl.value = lat;
    if (lngEl) lngEl.value = lng;
  }

  function getLatLng() {
    return {
      lat: parseFloat(q('.addr-wizard-lat')?.value || defaultLat),
      lng: parseFloat(q('.addr-wizard-lng')?.value || defaultLng),
    };
  }

  function setNextEnabled(enabled) {
    var btn = q('.addr-wizard-next');
    if (btn) btn.disabled = !enabled;
  }

  function setPreview(text, isError) {
    var box = q('.addr-wizard-preview');
    var span = q('.addr-wizard-preview-text');
    if (!box || !span) return;
    box.classList.remove(hiddenClass, 'alert-danger', 'alert-light', 'is-error');
    if (!text) {
      hideElement(box);
      span.textContent = '';
      return;
    }
    showElement(box);
    if (isError) {
      box.classList.add(previewErrorClass);
    } else if (previewOkClass) {
      box.classList.add(previewOkClass);
    }
    span.textContent = text;
  }

  function hideAutocomplete() {
    var list = q('.addr-wizard-autocomplete');
    if (list) {
      list.innerHTML = '';
      hideElement(list);
    }
  }

  function updateMapPosition(lat, lng, zoomTo) {
    if (!mapInstance || !mapMarker) return;

    if (hasGoogleMaps) {
      var pos = { lat: parseFloat(lat), lng: parseFloat(lng) };
      mapMarker.setPosition(pos);
      if (zoomTo) {
        mapInstance.setCenter(pos);
        mapInstance.setZoom(16);
      } else {
        mapInstance.panTo(pos);
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

  function fetchAddressDetails(lat, lng) {
    return fetch(reverseGeocodeUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ latitude: lat, longitude: lng }),
    })
    .then(function(r) { return r.ok ? r.json() : null; })
    .catch(function() { return null; });
  }

  function looksLikeCoords(value) {
    return /^-?\d+(\.\d+)?,\s*-?\d+(\.\d+)?$/.test(String(value || '').trim());
  }

  function applyLocation(lat, lng, label, zoomTo) {
    var token = ++lookupToken;
    setLatLng(lat, lng);
    setNextEnabled(false);
    setPreview(fetchingLabel, false);
    updateMapPosition(lat, lng, !!zoomTo);

    if (label && !looksLikeCoords(label)) {
      resolvedAddress = label;
      q('.addr-wizard-search').value = label;
      setPreview(label, false);
      setNextEnabled(true);
      return Promise.resolve(label);
    }

    return fetchAddressDetails(lat, lng).then(function(data) {
      if (token !== lookupToken) return null;

      var text = (data && data.address_text) ? data.address_text : '';
      if (!text && data && data.details && data.details.formatted_address) {
        text = data.details.formatted_address;
      }

      if (text && !looksLikeCoords(text)) {
        resolvedAddress = text;
        q('.addr-wizard-search').value = text;
        setPreview(text, false);
        setNextEnabled(true);
        return data;
      }

      setPreview(lookupFailedLabel, true);
      setNextEnabled(false);
      return null;
    });
  }

  function fillDetailsFromGeocode(data) {
    if (!data || !data.details) return Promise.resolve();

    var d = data.details;
    var form = getRoot().closest('form');
    if (!form) return Promise.resolve();

    function setField(name, value) {
      var el = form.querySelector('[name="' + name + '"]');
      if (el && value && !el.dataset.userEdited) {
        el.value = value;
      }
    }

    setField('address_line_1', d.address_line_1);
    setField('city', d.city);
    setField('zip_code', d.zip_code);

    var countrySelect = form.querySelector('.addr-wizard-country');
    var selectedCountryId = countrySelect ? countrySelect.value : null;

    if (countrySelect && d.country) {
      Array.prototype.forEach.call(countrySelect.options, function(opt) {
        if (opt.value && opt.text.trim().toLowerCase() === d.country.trim().toLowerCase()) {
          countrySelect.value = opt.value;
          selectedCountryId = opt.value;
        }
      });
    }

    var selectedText = q('.addr-wizard-selected-text');
    if (selectedText) {
      selectedText.textContent = resolvedAddress || d.formatted_address || '';
    }

    if (selectedCountryId) {
      return loadStatesForCountry(selectedCountryId, null, d.state);
    }

    return Promise.resolve();
  }

  function populateStateSelect(states, selectedStateId, selectedStateName) {
    var stateSelect = q('.addr-wizard-state');
    if (!stateSelect) return;

    var html = '<option value="">' + statePlaceholder + '</option>';
    var stateIds = states && typeof states === 'object' ? Object.keys(states) : [];

    stateIds.forEach(function(id) {
      html += '<option value="' + id + '">' + states[id] + '</option>';
    });

    stateSelect.innerHTML = html;

    if (stateIds.length > 0) {
      stateSelect.setAttribute('required', 'required');
    } else {
      stateSelect.removeAttribute('required');
    }

    if (selectedStateId) {
      stateSelect.value = String(selectedStateId);
    } else if (selectedStateName) {
      Array.prototype.forEach.call(stateSelect.options, function(opt) {
        if (opt.value && opt.text.trim().toLowerCase() === String(selectedStateName).trim().toLowerCase()) {
          stateSelect.value = opt.value;
        }
      });
    }
  }

  function loadStatesForCountry(countryId, selectedStateId, selectedStateName) {
    var stateSelect = q('.addr-wizard-state');
    if (!stateSelect) return Promise.resolve();

    if (!countryId) {
      populateStateSelect({}, null, null);
      return Promise.resolve();
    }

    return fetch(statesUrl + '?id=' + encodeURIComponent(countryId), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
    .then(function(r) { return r.ok ? r.json() : {}; })
    .then(function(states) {
      populateStateSelect(states || {}, selectedStateId, selectedStateName);
    })
    .catch(function() {
      populateStateSelect({}, null, null);
    });
  }

  function searchAddresses(query) {
    return fetch(searchAddressUrl + '?query=' + encodeURIComponent(query), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(function(r) { return r.json(); })
    .then(function(data) { return data.results || []; })
    .catch(function() { return []; });
  }

  function renderSearchResults(results) {
    var list = q('.addr-wizard-autocomplete');
    if (!list) return;

    list.innerHTML = '';
    if (!results.length) {
      hideElement(list);
      return;
    }

    results.forEach(function(item) {
      var li = document.createElement('li');
      li.textContent = item.label;
      li.addEventListener('click', function() {
        applyLocation(item.latitude, item.longitude, item.label, true);
        hideAutocomplete();
      });
      list.appendChild(li);
    });

    showElement(list);
  }

  function initGoogleMap() {
    var el = q('.addr-wizard-map');
    if (!el || typeof google === 'undefined') return;

    var coords = getLatLng();
    mapInstance = new google.maps.Map(el, {
      center: { lat: coords.lat, lng: coords.lng },
      zoom: 15,
    });

    mapMarker = new google.maps.Marker({
      position: { lat: coords.lat, lng: coords.lng },
      map: mapInstance,
      draggable: true,
    });

    mapMarker.addListener('dragend', function() {
      var pos = mapMarker.getPosition();
      applyLocation(pos.lat(), pos.lng(), null, false);
    });

    mapInstance.addListener('click', function(e) {
      mapMarker.setPosition(e.latLng);
      applyLocation(e.latLng.lat(), e.latLng.lng(), null, false);
    });

    if (hasExistingCoords) {
      applyLocation(coords.lat, coords.lng, null, true);
    } else {
      setLatLng(coords.lat, coords.lng);
    }
  }

  function initLeafletMap() {
    var el = q('.addr-wizard-map');
    if (!el || typeof L === 'undefined') return;

    var coords = getLatLng();
    mapInstance = L.map(el).setView([coords.lat, coords.lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap',
    }).addTo(mapInstance);

    mapMarker = L.marker([coords.lat, coords.lng], { draggable: true }).addTo(mapInstance);

    mapMarker.on('dragend', function() {
      var pos = mapMarker.getLatLng();
      applyLocation(pos.lat, pos.lng, null, false);
    });

    mapInstance.on('click', function(e) {
      mapMarker.setLatLng(e.latlng);
      applyLocation(e.latlng.lat, e.latlng.lng, null, false);
    });

    if (hasExistingCoords) {
      applyLocation(coords.lat, coords.lng, null, true);
    }
  }

  function refreshMapSize() {
    if (!mapInstance) return;

    if (hasGoogleMaps) {
      google.maps.event.trigger(mapInstance, 'resize');
      var coords = getLatLng();
      mapInstance.setCenter({ lat: coords.lat, lng: coords.lng });
    } else if (mapInstance.invalidateSize) {
      mapInstance.invalidateSize();
    }
  }

  function initMap() {
    if (mapInstance) return;
    if (hasGoogleMaps) {
      initGoogleMap();
    } else {
      initLeafletMap();
    }

    setTimeout(refreshMapSize, 150);
  }

  function useCurrentLocation() {
    if (!navigator.geolocation) {
      alert(geoUnsupportedLabel);
      return;
    }

    navigator.geolocation.getCurrentPosition(function(pos) {
      applyLocation(pos.coords.latitude, pos.coords.longitude, null, true);
    }, function() {
      alert(geoDeniedLabel);
    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
  }

  function bindEvents() {
    var root = getRoot();
    if (!root || root.dataset.bound === '1') return;
    root.dataset.bound = '1';

    var searchInput = q('.addr-wizard-search');
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        var query = searchInput.value.trim();
        if (query.length < 3) {
          hideAutocomplete();
          return;
        }
        searchTimer = setTimeout(function() {
          searchAddresses(query).then(renderSearchResults);
        }, 350);
      });
    }

    var gpsBtns = [q('.addr-wizard-use-gps'), q('.addr-wizard-map-gps')];
    gpsBtns.forEach(function(btn) {
      if (btn) btn.addEventListener('click', useCurrentLocation);
    });

    var nextBtn = q('.addr-wizard-next');
    if (nextBtn) {
      nextBtn.addEventListener('click', function() {
        var coords = getLatLng();
        fetchAddressDetails(coords.lat, coords.lng).then(function(data) {
          return fillDetailsFromGeocode(data).then(function() {
            var selectedText = q('.addr-wizard-selected-text');
            if (selectedText) {
              selectedText.textContent = resolvedAddress || (data && data.address_text) || '';
            }
            setStep(2);
          });
        });
      });
    }

    var backBtn = q('.addr-wizard-back');
    if (backBtn) {
      backBtn.addEventListener('click', function() {
        setStep(1);
        setTimeout(function() {
          initMap();
          if (mapInstance && hasGoogleMaps) {
            google.maps.event.trigger(mapInstance, 'resize');
            var coords = getLatLng();
            mapInstance.setCenter({ lat: coords.lat, lng: coords.lng });
          } else if (mapInstance) {
            mapInstance.invalidateSize();
          }
        }, 200);
      });
    }

    qa('[name="address_line_1"], [name="city"], [name="phone"]').forEach(function(el) {
      el.addEventListener('input', function() {
        el.dataset.userEdited = '1';
      });
    });

    var countrySelect = q('.addr-wizard-country');
    if (countrySelect) {
      countrySelect.addEventListener('change', function() {
        loadStatesForCountry(countrySelect.value, null, null);
      });
    }
  }

  window.initAddressWizard = function(id) {
    if (id) wizardId = id;
    bindEvents();
    setStep(startAtStep);

    if (startAtStep === 1) {
      if (hasGoogleMaps && typeof google !== 'undefined' && google.maps) {
        initMap();
      } else if (!hasGoogleMaps) {
        initMap();
      } else {
        document.addEventListener('locationMapApiReady', initMap, { once: true });
      }
    }
  };

  window.refreshAddressWizardMap = function(id) {
    if (id) wizardId = id;
    if (!mapInstance && startAtStep === 1) {
      initMap();
      return;
    }
    refreshMapSize();
  };

  if (!deferInit) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function() { window.initAddressWizard(wizardId); });
    } else {
      window.initAddressWizard(wizardId);
    }
  }
})();
</script>
