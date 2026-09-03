@php
  $geoip = geoip(get_visitor_IP());
  $shipping_country = $business_areas->where('iso_code', $geoip->iso_code)->first()
      ?? $business_areas->where('iso_code', get_default_geoip_country_iso())->first()
      ?? $business_areas->first();
  $shipping_state = null;
  if ($shipping_country && $geoip->state) {
      $shipping_state = \DB::table('states')
          ->select('id', 'name', 'iso_code')
          ->where([['country_id', '=', $shipping_country->id], ['iso_code', '=', $geoip->state]])
          ->first();
  }

  $shipping_zone = $shipping_country ? get_shipping_zone_of($item->shop_id, $shipping_country->id, optional($shipping_state)->id) : null;
  $shipping_options = isset($shipping_zone->id) ? getShippingRates($shipping_zone->id) : 'NaN';
@endphp

<div class="sf-pdp">
  <section>
    <div class="container md-100">
      <div class="row sc-product-item sf-pdp__stage" id="single-product-wrapper">
        <div class="col-xl-5 col-md-6 mb-4 mb-xl-0">
          <div class="sf-pdp__gallery">
            @include('theme::layouts.jqzoom', ['item' => $item, 'variants' => $variants])
          </div>
        </div>

        <div class="col-xl-4 col-md-6">
          <div class="sf-pdp__buy product-single">
            @include('theme::partials._product_info', ['item' => $item])

            <div class="product-info-options sf-pdp__options">
              <div class="select-box-wrapper">
                @foreach ($attributes as $attribute)
                  <div class="row product-attribute">
                    <div class="col-sm-3 col-4">
                      <span class="info-label" id="attr-{{ Str::slug($attribute->name) }}">{{ $attribute->name }}:</span>
                    </div>
                    <div class="col-sm-9 col-8 pl-1">
                      <select class="product-attribute-selector {{ $attribute->css_classes }}" id="attribute-{{ $attribute->id }}" required="required">
                        @foreach ($attribute->attributeValues as $option)
                          <option value="{{ $option->id }}" data-color="{{ $option->color ?? $option->value }}" {{ in_array($option->id, $item_attrs) ? 'selected' : '' }}>{{ $option->value }}</option>
                        @endforeach
                      </select>
                      <div class="help-block with-errors"></div>
                    </div>
                  </div>
                @endforeach
              </div>

              {{ Form::hidden('shipping_zone_id', isset($shipping_zone->id) ? $shipping_zone->id : null, ['id' => 'shipping-zone-id']) }}
              {{ Form::hidden('shipping_rate_id', null, ['id' => 'shipping-rate-id']) }}
              {{ Form::hidden('shipto_country_id', $shipping_country->id ?? null, ['id' => 'shipto-country-id']) }}
              {{ Form::hidden('shipto_state_id', optional($shipping_state)->id, ['id' => 'shipto-state-id']) }}

              @unless ($item->product->downloadable || $item->auctionable)
                <div id="calculation-section" class="sf-pdp__calc">
                  <div class="row">
                    <div class="col-3">
                      <span class="info-label" data-options='@json($shipping_options === "NaN" ? [] : $shipping_options)' id="shipping-options">@lang('theme.shipping'):</span>
                    </div>
                    <div class="col-9 pl-1">
                      <span id="summary-shipping-cost" data-value="0"></span>
                      <div id="product-info-shipping-detail">
                        <span>{{ strtolower(trans('theme.to')) }}
                          <a id="shipTo" class="ship_to" data-country="{{ $shipping_country->id ?? '' }}" data-state="{{ optional($shipping_state)->id }}" href="javascript:void(0);" data-toggle="tooltip" data-placement="top" title="{{ trans('theme.change_shipping_location') }}">
                            {{ $shipping_state ? $shipping_state->name : ($geoip->country ?? '') }}
                          </a>
                          <select id="width_tmp_select">
                            <option id="width_tmp_option"></option>
                          </select>
                        </span>
                        <span class="dynamic-shipping-rates" data-toggle="popover" title="{{ trans('theme.shipping_options') }}">
                          <span id="summary-shipping-carrier"></span>
                          <small class="ml-1 text-success"><i class="fas fa-caret-circle-down"></i></small>
                        </span>
                      </div>
                      <small id="delivery-time"></small>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-3">
                      <span class="info-label qtt-label">@lang('theme.quantity'):</span>
                    </div>
                    <div class="col-9 pl-0">
                      <div class="product-qty-wrapper">
                        <div class="product-info-qty-item">
                          <button class="product-info-qty product-info-qty-minus" type="button">-</button>
                          <input class="product-info-qty product-info-qty-input" data-name="product_quantity" data-min="{{ $item->min_order_quantity }}" data-max="{{ $item->stock_quantity }}" type="text" value="{{ $item->min_order_quantity }}">
                          <button class="product-info-qty product-info-qty-plus" type="button">+</button>
                        </div>
                        <span class="available-qty-count">@lang('theme.stock_count', ['count' => $item->stock_quantity])</span>
                      </div>
                    </div>
                  </div>

                  <div class="row" id="order-total-row">
                    <div class="col-3">
                      <span class="info-label">@lang('theme.total'):</span>
                    </div>
                    <div class="col-9 pl-0">
                      <span id="summary-total" class="text-muted">{{ trans('theme.notify.will_calculated_on_select') }}</span>
                    </div>
                  </div>
                </div>
              @endunless
            </div>

            @if ($item->auctionable)
              @include('auction::frontend._product_page')
            @endif

            @if (is_incevio_package_loaded('affiliate') && $item->hasAffiliateCommission())
              @include('affiliate::frontend.product_page_affiliate_section', ['item' => $item])
            @endif

            <div class="sf-pdp__cta flex-between-center flex-wrap sp-btns">
              @unless ($item->auctionable)
                <a href="{{ route('direct.checkout', $item->slug) }}" class="btn btn-primary btn-lg{{ $item->stock_quantity < 1 ? ' disabled' : '' }}" id="buy-now-btn"{{ $item->stock_quantity < 1 ? ' aria-disabled=true' : '' }}>
                  <i class="fal fa-rocket-launch"></i> @lang('theme.button.buy_now')
                </a>
                <a data-link="{{ route('cart.addItem', $item->slug) }}" class="btn btn-lg add-to-card-now-btn sc-add-to-cart{{ $item->stock_quantity < 1 ? ' disabled' : '' }}">
                  <i class="fal fa-shopping-cart"></i> @lang('theme.button.add_to_cart')
                </a>
              @endunless

              @if (is_incevio_package_loaded('comparison'))
                <a href="javascript:void(0);" data-link="{{ route('comparable.add', $item->id) }}" class="btn btn-default btn-lg add-to-product-compare" id="product-compare-btn">
                  <i class="fal fa-balance-scale"></i> @lang('theme.button.compare')
                </a>
              @endif

              @if ($item->product->inventories_count > 1)
                <a href="{{ storefront_product_offers_url($item) }}" class="d-none d-inline-block btn btn-link">
                  @lang('theme.view_more_offers', ['count' => $item->product->inventories_count])
                </a>
              @endif
            </div>
          </div>
        </div>

        <aside class="col-xl-3">
          <div class="sf-pdp__aside product-page-right-section">
            <div class="sf-pdp__seller seller-info">
              <div class="sf-pdp__seller-label">
                @lang('theme.sold_by')
                <a href="javascript:void(0);" data-toggle="modal" data-target="#shopReviewsModal" class="btn-link">
                  <i class="far fa-store"></i> {{ trans('theme.button.quick_view') }}
                </a>
              </div>

              @include('theme::partials._shop_logo_frame', ['shop' => $item->shop, 'frameSize' => 'sm', 'thumbSize' => 'tiny', 'fullSize' => 'medium'])

              <a href="{{ route('show.store', $item->shop->slug) }}" class="seller-info-name">
                {!! $item->shop->getQualifiedName() !!}
              </a>

              <div class="mt-2">
                @include('theme::layouts.ratings', ['ratings' => $item->shop->ratings, 'count' => $item->shop->ratings_count, 'shop' => $item->shop])
              </div>
            </div>

            @if (unserialize($item->key_features))
              <div class="sf-pdp__features">
                <h3>{!! trans('theme.section_headings.key_features') !!}</h3>
                <ul class="key-feature-list" id="item_key_features">
                  @foreach (unserialize($item->key_features) as $key_feature)
                    <li>
                      <i class="fal fa-check-double"></i>
                      <span>{{ $key_feature }}</span>
                    </li>
                  @endforeach
                </ul>
              </div>
            @endif

            @if ($linked_items->count() == 1)
              @php $t_linked_item = $linked_items->first(); @endphp
              <div class="sf-pdp__fbt-mini frequently-bought-section">
                <h3>@lang('theme.section_headings.frequently_bought_together')</h3>
                <a href="{{ storefront_product_url($t_linked_item) }}">
                  <div class="recent-items-img">
                    <img class="lazy" src="/images/square.webp" width="100px" data-src="{{ get_inventory_img_src($t_linked_item, 'small') }}" data-name="product_image" alt="{{ $t_linked_item->title }}" title="{{ $t_linked_item->title }}">
                  </div>
                </a>
                <div class="box-title">
                  <a href="{{ storefront_product_url($t_linked_item) }}">{{ $t_linked_item->title }}</a>
                </div>
                <div class="box-ratting">
                  @include('theme::partials._ratings', ['ratings' => $t_linked_item->ratings])
                </div>
                <div class="box-price mb-2">
                  @include('theme::partials._home_pricing', ['item' => $t_linked_item])
                </div>
                @if ($t_linked_item->auctionable)
                  @include('auction::frontend._place_bid_btn', ['item' => $t_linked_item])
                @else
                  <a href="javascript:void(0);" data-link="{{ route('cart.addItem', $t_linked_item->slug) }}" class="btn btn-primary sc-add-to-cart" tabindex="0">
                    <i class="fal fa-shopping-cart"></i>
                    <span class="d-none d-sm-inline-block ml-2">{{ trans('theme.add_to_cart') }}</span>
                  </a>
                @endif
              </div>
            @endif
          </div>
        </aside>
      </div>
    </div>
  </section>

  @if ($linked_items->count() > 1)
    <section class="sf-pdp__band">
      <div class="container md-100">
        <div class="frequently-bought-section">
          <h2>@lang('theme.section_headings.frequently_bought_together')</h2>
          <div class="sidebar-product-list flex-column flex-sm-row justify-content-center">
            @include('theme::partials._product_frequently_bought', ['products' => $linked_items])
          </div>
        </div>
      </div>
    </section>
  @endif

  @if ($alternative_items->count())
    <section class="sf-pdp__band">
      <div class="container">
        <header class="sf-pdp__band-head">
          <h2>{!! trans('theme.alternative_products') !!}</h2>
        </header>
        <div class="feature-items-inner">
          @include('theme::partials._product_horizontal', ['products' => $alternative_items])
        </div>
      </div>
    </section>
  @endif

  <section class="single-product-desc sf-pdp__details">
    <div class="container mt-4 md-100">
      <div class="row">
        <div class="col-md-12 px-0" id="product-desc-section">
          <div role="tabpanel">
            <ul class="nav nav-tabs sf-pdp__tabs" role="tablist">
              <li role="presentation" class="active">
                <a href="#desc_tab" aria-controls="desc_tab" role="tab" data-toggle="tab" aria-expanded="true">@lang('theme.product_desc')</a>
              </li>
              <li role="presentation">
                <a href="#seller_desc_tab" aria-controls="seller_desc_tab" role="tab" data-toggle="tab">@lang('theme.product_desc_seller')</a>
              </li>
              <li role="presentation">
                <a href="#reviews_tab" aria-controls="reviews_tab" role="tab" data-toggle="tab">@lang('theme.customer_reviews')</a>
              </li>
            </ul>

            <div class="tab-content sf-pdp__tab-body">
              <div role="tabpanel" class="tab-pane fade active in" id="desc_tab">
                {!! $item->product->description !!}

                @unless (config('system_settings.hide_technical_details_on_product_page'))
                  <h3>{{ trans('theme.technical_details') }}</h3>
                  <table class="table table-striped noborder sf-pdp__specs">
                    <tbody>
                      @if ($item->product->brand)
                        <tr class="noborder">
                          <th class="text-right noborder">{{ trans('theme.brand') }}:</th>
                          <td class="noborder" style="width: 65%;">{{ $item->product->brand }}</td>
                        </tr>
                      @endif
                      @if ($item->expiry_date)
                        <tr class="noborder">
                          <th class="text-right noborder">{{ trans('packages.pharmacy.expiry_date') }}:</th>
                          <td class="noborder" style="width: 65%;">{{ $item->expiry_date }}</td>
                        </tr>
                      @endif
                      @if ($item->product->model_number)
                        <tr class="noborder">
                          <th class="text-right noborder">{{ trans('theme.model_number') }}:</th>
                          <td class="noborder" style="width: 65%;">{{ $item->product->model_number }}</td>
                        </tr>
                      @endif
                      @if ($item->product->gtin_type && $item->product->gtin)
                        <tr class="noborder">
                          <th class="text-right noborder">{{ $item->product->gtin_type }}:</th>
                          <td class="noborder" style="width: 65%;">{{ $item->product->gtin }}</td>
                        </tr>
                      @endif
                      @if ($item->product->mpn)
                        <tr class="noborder">
                          <th class="text-right noborder">{{ trans('theme.mpn') }}:</th>
                          <td class="noborder" style="width: 65%;">{{ $item->product->mpn }}</td>
                        </tr>
                      @endif
                      @if ($item->sku)
                        <tr class="noborder">
                          <th class="text-right noborder">{{ trans('theme.sku') }}:</th>
                          <td class="noborder" id="item_sku" style="width: 65%;">{{ $item->sku }}</td>
                        </tr>
                      @endif
                      @if (config('system_settings.show_item_conditions'))
                        <tr class="noborder">
                          <th class="text-right noborder">{{ trans('theme.condition') }}:</th>
                          <td class="noborder" style="width: 65%;">
                            {{ $item->condition }}
                            @if ($item->condition_note)
                              <sup data-toggle="tooltip" data-placement="top" title="{{ $item->condition_note }}">
                                <i class="fas fa-question-circle" id="item_condition_note"></i>
                              </sup>
                            @endif
                          </td>
                        </tr>
                      @endif
                      @if (optional($item->product->manufacturer)->name)
                        <tr class="noborder">
                          <th class="text-right noborder">{{ trans('theme.manufacturer') }}:</th>
                          <td class="noborder" style="width: 65%;">{{ $item->product->manufacturer->name }}</td>
                        </tr>
                      @endif
                      @if ($item->product->origin)
                        <tr class="noborder">
                          <th class="text-right noborder">{{ trans('theme.origin') }}:</th>
                          <td class="noborder" style="width: 65%;">{{ $item->product->origin->name }}</td>
                        </tr>
                      @endif
                      <tr class="noborder">
                        <th class="text-right noborder">{{ trans('theme.availability') }}:</th>
                        <td class="noborder" style="width: 65%;">{{ $item->availability }}</td>
                      </tr>
                      @if ($item->min_order_quantity)
                        <tr class="noborder">
                          <th class="text-right noborder">{{ trans('theme.min_order_quantity') }}:</th>
                          <td class="noborder" id="item_min_order_qtt" style="width: 65%;">{{ $item->min_order_quantity }}</td>
                        </tr>
                      @endif
                      @if ($item->shipping_weight)
                        <tr class="noborder">
                          <th class="text-right noborder">{{ trans('theme.shipping_weight') }}:</th>
                          <td class="noborder" id="item_shipping_weight" style="width: 65%;">{{ $item->shipping_weight . ' ' . config('system_settings.weight_unit') }}</td>
                        </tr>
                      @endif
                      @if ($item->product->created_at)
                        <tr class="noborder">
                          <th class="text-right noborder">{{ trans('theme.first_listed_on', ['platform' => get_platform_title()]) }}:</th>
                          <td class="noborder" style="width: 65%;">{{ $item->product->created_at->toFormattedDateString() }}</td>
                        </tr>
                      @endif
                    </tbody>
                  </table>
                @endunless
              </div>

              <div role="tabpanel" class="tab-pane fade" id="seller_desc_tab">
                <div id="seller_seller_desc">
                  {!! clean_rich_html($item->description) !!}
                </div>
                @if ($item->shop->config->show_shop_desc_with_listing)
                  @if ($item->description)
                    <hr class="dashes my-4" />
                  @endif
                  <h3>{{ trans('theme.seller_info') }}</h3>
                  {!! clean_rich_html($item->shop->description) !!}
                @endif
                @if ($item->shop->config->show_refund_policy_with_listing && $item->shop->config->return_refund)
                  <hr class="dashes my-4" />
                  {!! $item->shop->config->return_refund !!}
                @endif
              </div>

              <div role="tabpanel" class="tab-pane fade" id="reviews_tab">
                <div class="reviews-tab">
                  @forelse($item->latestFeedbacks as $feedback)
                    <article class="sf-pdp__review">
                      <header>
                        <span class="review-user-name">{{ optional($feedback->customer)->getName() }}</span>
                        <span class="small">
                          <b class="text-success"><i class="fal fa-check"></i> @lang('theme.verified_purchase')</b>
                          <span class="text-muted">{{ $feedback->created_at->diffForHumans() }}</span>
                        </span>
                      </header>
                      <p class="my-2">{{ $feedback->comment }}</p>
                      @include('theme::layouts.ratings', ['ratings' => $feedback->rating, 'count' => $feedback->ratings_count])
                    </article>
                  @empty
                    <p class="lead text-center text-muted my-4">@lang('theme.no_reviews')</p>
                  @endforelse
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

@if (config('services.google.gtm_container_id'))
  @include('scripts.dataLayer.product_page')
@endif
