<div class="card-box text-center h-100 nearby-store-card">
  <a href="{{ route('show.store', $shop->slug) }}" class="text-reset">
    @if (config('system_settings.show_merchant_info_as_vendor'))
      <div class="thumb-lg d-flex thumbnail rounded-circle justify-content-center align-items-center mx-auto p-2">
        <img class="lazy w-100" src="{{ get_avatar_src($shop->owner, 'tiny_thumb') }}" data-src="{{ get_avatar_src($shop->owner, 'full') }}" alt="{{ $shop->name }}">
      </div>
      <h4 class="mb-1 mt-2">{!! $shop->owner->getName() !!}</h4>
    @else
      <div class="thumb-lg d-flex thumbnail rounded-circle justify-content-center align-items-center mx-auto p-2">
        <img class="lazy w-100" src="{{ get_storage_file_url(optional($shop->logoImage)->path, 'tiny_thumb') }}" data-src="{{ get_storage_file_url(optional($shop->logoImage)->path, 'full') }}" alt="{{ $shop->name }}">
      </div>
      <h4 class="mb-1 mt-2">{!! $shop->getQualifiedName(10) !!}</h4>
      @if (!empty($shop->reward_badge))
        <div class="sf-shop-card__badge mb-1">{!! $shop->reward_badge !!}</div>
      @endif
    @endif
  </a>

  @if (!empty($distance))
    <p class="text-muted mb-2 sf-shop-card__meta">
      <i class="fal fa-map-marker-alt"></i>
      {{ format_distance_km($distance) }}
    </p>
  @endif

  @if (isset($shop->active_inventories_count) && (int) $shop->active_inventories_count === 0)
    <p class="text-warning small mb-2">{{ trans('theme.no_products_listed_yet') }}</p>
  @elseif (empty($distance) && isset($shop->inventories_count))
    <p class="sf-shop-card__meta mb-2">
      <i class="fal fa-box-open"></i>
      {{ $shop->inventories_count }} {{ trans('theme.active_listings') }}
    </p>
  @endif

  @include('theme::layouts.ratings', ['ratings' => $shop->ratings, 'count' => $shop->ratings_count ?? 0])

  <a href="{{ route('show.store', $shop->slug) }}" class="btn btn-default btn-rounded mt-3 waves-effect w-md waves-light">
    {{ trans('theme.visit_shop_page') }}
  </a>
</div>
