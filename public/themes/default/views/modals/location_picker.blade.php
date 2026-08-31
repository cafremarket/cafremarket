<div class="modal" id="locationModal" tabindex="-1" role="dialog" aria-labelledby="locationModalLabel" aria-hidden="true">
  <div class="modal-dialog location-modal modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div class="modal-icon">
          <i class="fal fa-map-marker-alt fa-2x text-primary"></i>
        </div>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body pt-2">
        <div class="text-center mb-3">
          <h4 id="locationModalLabel">{{ trans('theme.where_deliver') }}</h4>
          <p class="text-muted mb-0">{{ trans('theme.where_deliver_help') }}</p>
        </div>

        <form id="locationForm">
          @csrf
          <div class="form-group location-search-wrap">
            <label>{{ trans('theme.search_address') }}</label>
            <input type="text" id="locationSearch" class="form-control form-control-lg" placeholder="{{ trans('theme.search_address_placeholder') }}" autocomplete="off">
            <ul id="locationAutocompleteList" class="location-autocomplete-list d-none"></ul>
          </div>

          <div class="location-map-wrap">
            <div id="locationMapCanvas" class="location-map-canvas"></div>
            <button type="button" id="mapCurrentLocationBtn" class="location-map-current-btn" title="{{ trans('theme.map_current_location') }}" aria-label="{{ trans('theme.map_current_location') }}">
              <i class="fal fa-crosshairs"></i>
            </button>
            <p class="text-muted small mt-2 mb-0">{{ trans('theme.drag_map_to_adjust_pin') }}</p>
          </div>

          <div class="text-center my-3">
            <span class="text-muted">{{ trans('theme.or') }}</span>
          </div>

          <button type="button" id="useCurrentLocationBtn" class="btn btn-outline-primary btn-block btn-lg btn-round">
            <i class="fal fa-crosshairs"></i> {{ trans('theme.use_current_location') }}
          </button>

          <input type="hidden" name="latitude" id="locationLatitude" value="{{ session('buyer_latitude') }}">
          <input type="hidden" name="longitude" id="locationLongitude" value="{{ session('buyer_longitude') }}">
          <input type="hidden" name="address_text" id="locationAddressText" value="{{ session('buyer_address_text') }}">

          <div id="locationPreview" class="alert alert-light mt-3 {{ session('buyer_address_text') ? '' : 'd-none' }}">
            <i class="fal fa-check-circle text-success"></i>
            <span id="locationPreviewText">{{ session('buyer_address_text') }}</span>
          </div>
        </form>
      </div>

      <div class="modal-footer border-0">
        <button type="submit" form="locationForm" id="saveLocationBtn" class="btn btn-primary btn-block btn-lg btn-round" disabled>
          {{ trans('theme.confirm_location') }}
        </button>
      </div>
    </div>
  </div>
</div>

<style>
  #locationModal.modal.in {
    display: flex !important;
    align-items: flex-start;
    justify-content: center;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    padding: 24px 15px;
  }

  #locationModal .modal-dialog {
    margin: 0;
    width: 100%;
    max-width: 680px;
    flex-shrink: 0;
  }

  #locationModal .modal-content {
    overflow: visible;
    border-radius: 12px;
  }

  #locationModal .modal-body {
    padding: 8px 24px 0;
    overflow: visible;
  }

  #locationModal .modal-footer {
    padding: 16px 24px 24px;
    margin: 0;
  }

  #locationModal .modal-footer .btn {
    margin: 0;
  }

  #locationModal .location-search-wrap {
    position: relative;
    z-index: 20;
  }

  #locationModal .location-autocomplete-list {
    position: absolute;
    top: calc(100% - 4px);
    left: 0;
    right: 0;
    max-height: 240px;
    overflow-y: auto;
    margin: 0;
    padding: 6px 0;
    list-style: none;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    z-index: 100060;
  }

  #locationModal .location-autocomplete-list li {
    padding: 10px 14px;
    cursor: pointer;
    font-size: 14px;
    line-height: 1.4;
    border-bottom: 1px solid #f3f4f6;
  }

  #locationModal .location-autocomplete-list li:last-child {
    border-bottom: none;
  }

  #locationModal .location-autocomplete-list li:hover,
  #locationModal .location-autocomplete-list li.active {
    background: #fff7ed;
    color: #c2410c;
  }

  #locationModal .location-map-wrap {
    position: relative;
    margin-top: 16px;
  }

  #locationModal .location-map-canvas {
    width: 100%;
    height: 280px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    background: #f3f4f6;
    overflow: hidden;
  }

  #locationModal .location-map-current-btn {
    position: absolute;
    right: 12px;
    bottom: 44px;
    z-index: 500;
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 50%;
    background: #fff;
    color: var(--primary-color, #f97316);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.18);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
  }

  #locationModal .location-map-current-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.22);
  }

  #locationModal .location-map-current-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    transform: none;
  }

  .pac-container {
    z-index: 100060 !important;
  }

  @media (max-height: 820px) {
    #locationModal.modal.in {
      padding: 12px 15px;
    }

    #locationModal .modal-body {
      max-height: calc(100vh - 200px);
      overflow-y: auto;
    }

    #locationModal .location-map-canvas {
      height: 220px;
    }
  }
</style>
