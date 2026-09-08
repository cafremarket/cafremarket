<div class="modal-dialog modal-xl sf-quickview-dialog" role="document">
  <div class="modal-content sf-quickview">
    <button type="button" class="sf-quickview__close" data-dismiss="modal" aria-label="{{ trans('theme.button.close') ?? 'Close' }}">
      <i class="fal fa-times"></i>
    </button>

    <div class="row sc-product-item sf-quickview__body">
      <div class="col-md-5 col-sm-6 sf-quickview__gallery">
        @include('theme::layouts.jqzoom', ['item' => $item])
      </div>

      <div class="col-md-7 col-sm-6 sf-quickview__info">
        <div class="product-single">
          @include('theme::partials._product_info', ['zoomID' => 'quickViewZoom', 'item' => $item])

          @if ($item->key_features)
            <div class="sf-quickview__features">
              <h4>{!! trans('theme.section_headings.key_features') !!}</h4>
              <ul class="key-feature-list">
                @foreach (unserialize($item->key_features) as $key_feature)
                  <li>
                    <i class="fal fa-check-double"></i>
                    <span>{{ $key_feature }}</span>
                  </li>
                @endforeach
              </ul>
            </div>
          @endif

          @if (is_incevio_package_loaded('wholesale') && !$item->wholesale_prices->isEmpty())
            <div class="sf-quickview__wholesale">
              @include('wholesale::quickview_price_table')
            </div>
          @endif

          <div class="sf-quickview__actions">
            @if ($item->auctionable)
              <a href="{{ storefront_product_url($item) }}" class="btn sf-btn-primary btn-lg">
                <i class="fal fa-gavel"></i> {{ trans('packages.auction.place_bid') }}
              </a>
            @else
              <a href="{{ route('direct.checkout', $item->slug) }}" class="btn sf-btn-primary btn-lg" id="buy-now-btn">
                <i class="fas fa-rocket"></i> @lang('theme.button.buy_now')
              </a>

              <a href="javascript:void(0);" data-link="{{ route('cart.addItem', $item->slug) }}" class="btn btn-default btn-lg sc-add-to-cart" data-dismiss="modal">
                <i class="fas fa-shopping-bag"></i> @lang('theme.button.add_to_cart')
              </a>
            @endif

            <a href="{{ storefront_product_url($item) }}" class="btn btn-link sf-quickview__view-details">
              @lang('theme.button.view_product_details') <i class="fal fa-arrow-right"></i>
            </a>
          </div>

          @if ($item->product->inventories_count > 1)
            <a href="{{ storefront_product_offers_url($item) }}" class="sf-quickview__offers-link">
              @lang('theme.view_more_offers', ['count' => $item->product->inventories_count])
            </a>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
