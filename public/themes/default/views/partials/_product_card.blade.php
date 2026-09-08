{{-- Canonical product card used everywhere: category/shop grids, homepage sliders
     (featured, deal of the day, best selling, related items, etc.) and search results.
     Keeps the original functional classes/data-attributes (sc-add-to-cart, itemQuickView,
     ratings/pricing partials) so existing cart/quick-view JS keeps working unmodified.
     The image box is a fixed-ratio frame (object-fit: cover) so a tall, wide, or tiny
     source image never changes the card's size — every card in a row lines up the same.
     Pass an optional $distance (km) to show it next to the store name. --}}
<div class="product product-grid-view sc-product-item sf-product-card">
  <ul class="product-info-labels">
    @foreach ($item->getLabels() as $label)
      <li>{!! $label !!}</li>
    @endforeach
  </ul>

  <div class="product-img-wrap sf-product-card__media">
    <img class="product-img-primary lazy" src="{{ get_product_img_src($item, 'tiny_thumb') }}" data-src="{{ get_product_img_src($item, 'full') }}" alt="{{ $item->title }}" title="{{ $item->title }}" />

    <img class="product-img-alt lazy" src="{{ get_product_img_src($item, 'tiny_thumb', 'alt') }}" data-src="{{ get_product_img_src($item, 'full', 'alt') }}" alt="{{ $item->title }}" title="{{ $item->title }}" />

    <a class="product-link" href="{{ storefront_product_url($item) }}"></a>
  </div>

  <div class="product-actions btn-group radius">
    @if (is_incevio_package_loaded('comparison'))
      @include('comparison::_product_list_compare_btn')
    @endif

    <a class="btn btn-default itemQuickView" href="javascript:void(0);" data-link="{{ storefront_product_quickview_url($item) }}" rel="nofollow noindex" data-toggle="tooltip" title="@lang('theme.button.quick_view')" aria-label="@lang('theme.button.quick_view')">
      <i class="far fa-eye"></i> <span>@lang('theme.button.quick_view')</span>
    </a>

    @if (is_incevio_package_loaded('auction') && $item->auctionable)
      <a class="btn btn-primary" href="{{ storefront_product_url($item) }}" data-toggle="tooltip" title="{{ trans('packages.auction.place_bid') }}" aria-label="{{ trans('packages.auction.place_bid') }}">
        <i class="fal fa-gavel"></i>
      </a>
    @else
      <a class="btn btn-primary sc-add-to-cart add-to-card-mod" data-link="{{ route('cart.addItem', $item->slug) }}" data-toggle="tooltip" title="@lang('theme.add_to_cart')" aria-label="@lang('theme.add_to_cart')">
        <i class="far fa-shopping-cart"></i>
      </a>
    @endif
  </div>

  <div class="product-info">
    @if (is_incevio_package_loaded('auction') && $item->auctionable)
      @include('auction::frontend._auction_status')
    @else
      @include('theme::layouts.ratings', ['ratings' => $item->ratings, 'count' => $item->ratings_count])
    @endif

    <a href="{{ storefront_product_url($item) }}" class="product-info-title" data-name="product_name" aria-label="{{ $item->title }}">{{ $item->title }}</a>

    @if ($item->shop)
      <a href="{{ route('show.store', $item->shop->slug) }}" class="sf-product-card__shop">
        <i class="fal fa-store"></i>
        <span class="sf-product-card__shop-name">{{ $item->shop->name }}</span>
        @if (! empty($distance))
          <span class="sf-product-card__distance">&middot; {{ format_distance_km($distance) }}</span>
        @endif
      </a>
    @endif

    <div class="product-info-availability">
      @lang('theme.availability'): <span>{{ $item->stock_quantity > 0 ? trans('theme.in_stock') : trans('theme.out_of_stock') }}</span>
    </div>

    @include('theme::layouts.pricing', ['item' => $item])

    <ul class="product-info-feature-list">
      @if (config('system_settings.show_item_conditions'))
        <li>{!! $item->condition !!}</li>
      @endif
    </ul>
  </div>
</div>
