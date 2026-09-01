<div class="sf-empty-stores text-center py-5">
  <div class="sf-empty-stores__icon">
    <i class="fal fa-store-slash"></i>
  </div>
  <h3 class="sf-empty-stores__title">{{ $title ?? trans('theme.no_store_found') }}</h3>
  @if (!empty($message))
    <p class="sf-empty-stores__text">{{ $message }}</p>
  @endif
  @if (!empty($showLocationButton))
    <button type="button" class="btn sf-btn-primary btn-round mt-2" data-toggle="modal" data-target="#locationModal">
      {{ $locationButtonText ?? trans('theme.change_location') }}
    </button>
  @endif
</div>
