{{-- Full store block for single-page multi-store checkout (no switching). --}}
@php
  $dec = $dec ?? (is_non_decimal_currency() ? 0 : config('system_settings.decimals', 2));
  $shop = $cart->shop;
  $cart_total = 0;
  $cartBlocked = !empty($cart->out_of_range) || !empty($cart->needs_delivery_location);
@endphp

<div class="sf-checkout__store-block shopping-cart-wrapper {{ $cartBlocked ? 'is-oor' : '' }}"
     id="cartId{{ $cart->id }}"
     data-cart="{{ $cart->id }}"
     data-cart-type="{{ $cart->is_digital ? 'digital' : 'physical' }}"
     data-out-of-range="{{ !empty($cart->out_of_range) ? '1' : '0' }}">

  {{ Form::hidden('cart_ids[]', $cart->id) }}
  {{ Form::hidden('cart_weight_'.$cart->id, $cart->shipping_weight, ['id' => 'cartWeight' . $cart->id]) }}
  {{ Form::hidden('free_shipping_'.$cart->id, $cart->is_free_shipping(), ['id' => 'freeShipping' . $cart->id]) }}
  {{ Form::hidden('shop_id_'.$cart->id, optional($shop)->id, ['id' => 'shop-id' . $cart->id]) }}
  {{ Form::hidden('tax_id_'.$cart->id, isset($shipping_zones[$cart->id]->id) ? $shipping_zones[$cart->id]->tax_id : null, ['id' => 'tax-id' . $cart->id]) }}
  {{ Form::hidden('taxrate_'.$cart->id, $cart->taxrate, ['id' => 'cart-taxrate' . $cart->id]) }}
  {{ Form::hidden('shipping_zone_id_'.$cart->id, isset($shipping_zones[$cart->id]->id) ? $shipping_zones[$cart->id]->id : $cart->shipping_zone_id, ['id' => 'zone-id' . $cart->id]) }}
  {{ Form::hidden('shipping_rate_id_'.$cart->id, $cart->shipping_rate_id, ['id' => 'shipping-rate-id' . $cart->id]) }}
  {{ Form::hidden('ship_to_country_id_'.$cart->id, $cart->ship_to_country_id, ['id' => 'shipto-country-id' . $cart->id]) }}
  {{ Form::hidden('ship_to_state_id_'.$cart->id, $cart->ship_to_state_id, ['id' => 'shipto-state-id' . $cart->id]) }}
  {{ Form::hidden('coupon_raw_'.$cart->id, json_encode($cart->coupon), ['id' => 'coupon-raw' . $cart->id]) }}
  {{ Form::hidden('handling_cost_'.$cart->id, $cart->handling_cost > 0 ? get_formated_price_value($cart->handling_cost) : getHandelingCostOf($cart->shop_id), ['id' => 'handling-cost' . $cart->id]) }}

  @if (!$cart->is_digital && is_incevio_package_loaded('packaging'))
    @php
      $default_packaging = $cart->shippingPackage
        ?? (optional(optional($shop)->packagings)->where('default', 1)->first() ?? ($platformDefaultPackaging ?? null));
    @endphp
    {{ Form::hidden('packaging_id_'.$cart->id, $default_packaging ? $default_packaging->id : null, ['id' => 'packaging-id' . $cart->id]) }}
  @endif

  <div class="sf-checkout__store-block-head">
    <div class="sf-checkout__seller flex-between-center">
      <div class="logo-wrapper d-flex align-items-center">
        @include('theme::partials._shop_logo_frame', ['shop' => $shop, 'frameSize' => 'sm', 'thumbSize' => 'tiny_thumb', 'fullSize' => 'medium'])
        <div class="ml-2">
          @if ($shop && $shop->slug)
            <a href="{{ route('show.store', $shop->slug) }}" class="seller-info-name">
              {!! $shop->getQualifiedName(40) !!}
            </a>
          @else
            <strong>{{ optional($shop)->name ?? ('Store #'.$cart->shop_id) }}</strong>
          @endif
          <div class="sf-checkout__store-tab-meta">
            {{ $cart->inventories->count() }} {{ \Illuminate\Support\Str::plural('item', $cart->inventories->count()) }}
          </div>
        </div>
      </div>
    </div>

    @if (!empty($cart->needs_delivery_location))
      <div class="notice notice-warning notice-sm mt-2 mb-0">
        <strong>{{ trans('theme.warning') }}</strong>
        {{ trans('theme.notify.set_location_for_delivery') }}
      </div>
    @elseif (!empty($cart->out_of_range))
      <div class="notice notice-danger notice-sm mt-2 mb-0">
        <strong>{{ trans('theme.out_of_delivery_range') }}</strong>
        {{ trans('theme.notify.product_out_of_delivery_range', [
          'store' => optional($shop)->name ?? 'This store',
          'distance' => $cart->delivery_distance_km ?? '—',
          'radius' => $cart->service_radius_km ?? '—',
        ]) }}
      </div>
    @endif
  </div>

  <div class="table-responsive">
    <table class="table shopping-cart-item-table" id="table{{ $cart->id }}">
      <thead>
        <tr>
          <th width="90px">{{ trans('theme.image') }}</th>
          @if ($cart->is_digital)
            <th>{{ trans('theme.description') }}</th>
            <th>{{ trans('theme.price') }}</th>
          @else
            <th width="52%" class="hidden-sm hidden-xs">{{ trans('theme.description') }}</th>
            <th>{{ trans('theme.price') }}</th>
            <th>{{ trans('theme.quantity') }}</th>
            <th>{{ trans('theme.total') }}</th>
          @endif
          <th>&nbsp;</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($cart->inventories as $item)
          @php
            if ($cart->auction_bid_id) {
                $unit_price = get_formated_value($cart->bid->amount_in_system_currency);
            } elseif (is_incevio_package_loaded('wholesale')) {
                $unit_price = get_wholesale_unit_price($item, $item->pivot->quantity);
            } else {
                $unit_price = get_formated_value($item->current_sale_price());
            }
            $item_total = $unit_price * $item->pivot->quantity;
            $cart_total += $item_total;
          @endphp
          <tr class="cart-item-tr">
            <td>
              <input type="hidden" class="freeShipping{{ $cart->id }}" value="{{ $item->free_shipping }}">
              <input type="hidden" id="unitWeight{{ $item->id }}" value="{{ $item->shipping_weight }}">
              {{ Form::hidden('shipping_weight[' . $item->id . ']', $item->shipping_weight * $item->pivot->quantity, ['id' => 'itemWeight' . $item->id, 'class' => 'itemWeight' . $cart->id]) }}
              <img class="lazy item-img" src="{{ get_product_img_src($item, 'tiny') }}" data-src="{{ get_product_img_src($item, 'medium') }}" alt="{{ $item->slug }}" title="{{ $item->slug }}" />
            </td>
            <td class="hidden-sm hidden-xs">
              <a href="{{ storefront_product_url($item) }}" class="product-info-title">
                {{ $item->pivot->item_description }}
                @if (is_incevio_package_loaded('wallet'))
                  @include('wallet::_credit_back_percentage_badge', ['rw_percentage' => $item->reward_percentage])
                @endif
                @if ($item->isOutOfStock())
                  <span class="label label-danger text-right ml-3">{{ trans('mobile.out_of_stock') }}</span>
                @endif
              </a>
            </td>
            @unless ($cart->is_digital)
              <td class="shopping-cart-item-price">
                <span>
                  {{ get_currency_prefix() }}<span id="item-price{{ $cart->id . '-' . $item->id }}" data-value="{{ $unit_price }}">{{ get_formated_decimal(get_formated_price_value($unit_price), false, $dec) }}</span>{{ get_currency_suffix() }}
                </span>
              </td>
              <td>
                <div class="product-info-qty-item d-inline-flex">
                  <button type="button" class="product-info-qty product-info-qty-minus">-</button>
                  <input name="quantity[{{ $item->id }}]" id="itemQtt{{ $item->id }}" class="product-info-qty product-info-qty-input" data-cart="{{ $cart->id }}" data-item="{{ $item->id }}" data-min="{{ $item->min_order_quantity }}" data-max="{{ $item->stock_quantity }}" type="text" value="{{ $item->pivot->quantity }}">
                  <button type="button" class="product-info-qty product-info-qty-plus">+</button>
                </div>
              </td>
            @endunless
            <td>
              <span>
                {{ get_currency_prefix() }}<span id="item-total{{ $cart->id . '-' . $item->id }}" class="item-total{{ $cart->id }}" data-value="{{ get_formated_price_value($item_total) }}">{{ get_formated_decimal(get_formated_price_value($item_total), false, $dec) }}</span>{{ get_currency_suffix() }}
              </span>
            </td>
            <td>
              @unless ($cart->auction_bid_id)
                <a href="javascript:void(0);" class="cart-item-remove" data-cart="{{ $cart->id }}" data-item="{{ $item->id }}" data-toggle="tooltip" title="@lang('theme.remove_item')">&times;</a>
              @endunless
            </td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="6">
            <div class="input-group w-100 radius">
              <span class="input-group-addon"><i class="fas fa-ticket no-fill"></i></span>
              <input name="coupon_{{ $cart->id }}" value="{{ $cart->coupon ? $cart->coupon->code : null }}" id="coupon{{ $cart->id }}" class="form-control" type="text" placeholder="@lang('theme.placeholder.have_coupon_from_seller')">
              <span class="input-group-btn">
                <button class="btn btn-default apply_seller_coupon" type="button" data-cart="{{ $cart->id }}">@lang('theme.button.apply_coupon')</button>
              </span>
            </div>
          </td>
        </tr>
        <tr>
          <td colspan="4" class="text-right"><strong>{{ trans('theme.subtotal') }}</strong></td>
          <td colspan="2">
            <strong>
              {{ get_currency_prefix() }}
              <span id="summary-total{{ $cart->id }}" data-value="{{ $cart_total }}">{{ get_formated_decimal($cart_total, false, $dec) }}</span>
              {{ get_currency_suffix() }}
            </strong>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="notice notice-danger notice-sm hidden" id="store-unavailable-notice{{ $cart->id }}">
    <strong>{{ trans('theme.warning') }}</strong> @lang('theme.notify.store_not_available')
  </div>
</div>
