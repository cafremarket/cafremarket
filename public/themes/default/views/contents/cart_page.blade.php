<section class="sf-checkout px-xl-0 px-3 mt-3 mt-lg-0">
  <div class="container lg-100">
    @php
      $dec = is_non_decimal_currency() ? 0 : config('system_settings.decimals', 2);
    @endphp

    @if (Session::has('error'))
      <div class="notice notice-danger notice-sm mb-3">
        <strong>{{ trans('theme.error') }}</strong> {{ Session::get('error') }}
      </div>
    @endif

    @if ($carts->count() > 0)
      <header class="sf-checkout__intro">
        <h1>{{ trans('theme.checkout') }}</h1>
        <p>{{ trans('theme.shopping_cart') }}</p>
      </header>

      @foreach ($carts as $cart)
        @php
          $cart_total = 0;
          $shop = $cart->shop;
          $cartPaymentMethods = vendor_get_paid_directly() ? ($shop->paymentMethods ?? collect()) : $paymentMethods;

          $packaging_options = null;
          if (!$cart->is_digital && is_incevio_package_loaded('packaging')) {
              $packaging_options = optional($shop)->packagings;

              if ($shop) {
                  $default_packaging = $cart->shippingPackage ?? (optional($shop->packagings)->where('default', 1)->first() ?? $platformDefaultPackaging);
              } else {
                  $default_packaging = $cart->shippingPackage ?? $platformDefaultPackaging;
              }
          }

          $selectedAddress = null;
          $pre_select = null;
          if (isset($customer) && $customer && $customer->addresses) {
              foreach ($customer->addresses as $address) {
                  if ($pre_select !== null) {
                      continue;
                  }
                  if ($customer->addresses->count() == 1) {
                      $pre_select = $address;
                  } elseif (Request::has('address') && Request::get('address') == $address->id) {
                      $pre_select = $address;
                  } elseif ($cart->ship_to && $cart->ship_to == $address->id) {
                      $pre_select = $address;
                  } elseif ($cart->ship_to_country_id == $address->country_id && $cart->ship_to_state_id == $address->state_id) {
                      $pre_select = $address;
                  } elseif ($cart->ship_to == null && $address->address_type === 'Shipping') {
                      $pre_select = $address;
                  }
              }
              $selectedAddress = $pre_select ?: $customer->addresses->first();
          }
        @endphp

        {!! Form::open(['route' => ['order.create', $cart], 'id' => 'formId' . $cart->id, 'name' => 'checkoutForm', 'files' => true, 'data-toggle' => 'validator', 'autocomplete' => 'off', 'novalidate', 'class' => 'sf-checkout__form']) !!}

        <div class="row shopping-cart-wrapper sf-checkout__card mb-4 {{ $expressId == $cart->id ? 'selected' : '' }}" id="cartId{{ $cart->id }}" data-cart="{{ $cart->id }}" data-cart-type="{{ $cart->is_digital ? 'digital' : 'physical' }}">
          <div class="col-lg-8 px-3 py-3">
            {{ Form::hidden('cart_id', $cart->id, ['id' => 'checkout-id']) }}
            {{ Form::hidden('cart_id_ref', $cart->id, ['id' => 'cart-id' . $cart->id]) }}
            {{ Form::hidden('cart_weight', $cart->shipping_weight, ['id' => 'cartWeight' . $cart->id]) }}
            {{ Form::hidden('free_shipping', $cart->is_free_shipping(), ['id' => 'freeShipping' . $cart->id]) }}
            {{ Form::hidden('shop_id', $shop->id, ['id' => 'shop-id' . $cart->id]) }}
            {{ Form::hidden('tax_id', isset($shipping_zones[$cart->id]->id) ? $shipping_zones[$cart->id]->tax_id : null, ['id' => 'tax-id' . $cart->id]) }}
            {{ Form::hidden('taxrate', $cart->taxrate, ['id' => 'cart-taxrate' . $cart->id]) }}
            {{ Form::hidden('shipping_zone_id', isset($shipping_zones[$cart->id]->id) ? $shipping_zones[$cart->id]->id : $cart->shipping_zone_id, ['id' => 'zone-id' . $cart->id]) }}
            {{ Form::hidden('shipping_rate_id', $cart->shipping_rate_id, ['id' => 'shipping-rate-id' . $cart->id]) }}
            {{ Form::hidden('ship_to_country_id', $cart->ship_to_country_id, ['id' => 'shipto-country-id' . $cart->id]) }}
            {{ Form::hidden('ship_to_state_id', $cart->ship_to_state_id, ['id' => 'shipto-state-id' . $cart->id]) }}
            {{ Form::hidden('coupon_raw', json_encode($cart->coupon), ['id' => 'coupon-raw' . $cart->id]) }}
            {{ Form::hidden('handling_cost', $cart->handling_cost > 0 ? get_formated_price_value($cart->handling_cost) : getHandelingCostOf($cart->shop_id), ['id' => 'handling-cost' . $cart->id]) }}

            @if (!$cart->is_digital && is_incevio_package_loaded('packaging'))
              {{ Form::hidden('packaging_id', $default_packaging ? $default_packaging->id : null, ['id' => 'packaging-id' . $cart->id]) }}
            @endif

            <div class="sf-checkout__seller flex-between-center">
              <div class="logo-wrapper">
                @include('theme::partials._shop_logo_frame', ['shop' => $shop, 'frameSize' => 'sm', 'thumbSize' => 'tiny_thumb', 'fullSize' => 'medium'])
                <a href="{{ route('show.store', $shop->slug) }}" class="seller-info-name ml-2">
                  {!! $shop->getQualifiedName(10) !!}
                </a>
              </div>
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
                          <span class="d-inline-flex">
                            {{ get_currency_prefix() }}
                            <span id="item-price{{ $cart->id . '-' . $item->id }}" data-value="{{ $unit_price }}">
                              {{ number_format(get_formated_price_value($unit_price), $dec, '.', '') }}
                            </span>
                            {{ get_currency_suffix() }}
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
                        <span class="d-inline-flex">
                          {{ get_currency_prefix() }}
                          <span id="item-total{{ $cart->id . '-' . $item->id }}" class="item-total{{ $cart->id }}">
                            {{ number_format(get_formated_price_value($item_total), $dec, '.', '') }}
                          </span>
                          {{ get_currency_suffix() }}
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
                        <input name="coupon" value="{{ $cart->coupon ? $cart->coupon->code : null }}" id="coupon{{ $cart->id }}" class="form-control" type="text" placeholder="@lang('theme.placeholder.have_coupon_from_seller')">
                        <span class="input-group-btn">
                          <button class="btn btn-default apply_seller_coupon" type="button" data-cart="{{ $cart->id }}">@lang('theme.button.apply_coupon')</button>
                        </span>
                      </div>
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>

            @if ($cart->auction_bid_id)
              @include('auction::frontend.notices')
            @endif

            <div class="notice notice-danger notice-sm hidden" id="store-unavailable-notice{{ $cart->id }}">
              <strong>{{ trans('theme.warning') }}</strong> @lang('theme.notify.store_not_available')
            </div>
            <div class="notice notice-warning notice-sm mb-3" id="checkout-notice" style="display: none;">
              <strong>{{ trans('theme.warning') }}</strong>
              <span id="checkout-notice-msg"></span>
            </div>

            @if (is_panel_user_on_storefront())
              <div class="notice notice-warning notice-sm">
                <strong>{{ trans('theme.warning') }}</strong> {{ panel_user_storefront_message() }}
              </div>
            @elseif (\App\Models\SystemConfig::CustomerNeedsApproval() && !allow_checkout())
              <div class="notice notice-warning notice-sm">
                <strong>{{ trans('theme.warning') }}</strong> {{ trans('help.account_needs_approval') }}
              </div>
            @endif

            <div class="sf-checkout__address">
              @if ($cart->is_digital)
                <h3 class="sf-checkout__section-title">{{ trans('theme.billing_address') }}</h3>
              @else
                <div class="sf-checkout__fulfilment">
                  <label class="sf-checkout__fulfil-opt">
                    <input class="form-check-input" type="radio" name="fulfilment_type" id="fulfilment_type_deliver" value="{{ \App\Models\Order::FULFILMENT_TYPE_DELIVER }}" checked>
                    <span><i class="far fa-shipping-fast"></i> {{ trans('theme.ship_to') }}</span>
                  </label>
                  @if ($shop->isPickupEnabled())
                    <label class="sf-checkout__fulfil-opt">
                      <input class="form-check-input" type="radio" name="fulfilment_type" id="fulfilment_type_pickup" value="{{ \App\Models\Order::FULFILMENT_TYPE_PICKUP }}">
                      <span><i class="far fa-shopping-basket"></i> {{ trans('theme.pickup_from') }}</span>
                    </label>
                  @endif
                </div>
              @endif

              <div class="form-group mb-4 hidden" id="pickup_details">
                <div class="row warehouse-address-list">
                  @forelse ($shop->warehouses ?? [] as $warehouse)
                    <div class="col-sm-12 col-md-6 textClass">
                      <div class="address-list-item">
                        <i class="fa fa-home"></i><strong> {!! $warehouse->name !!} </strong><br>
                        <i class="fa fa-map-marker"></i> <em>{{ trans('app.address') }} :</em>
                        {!! $warehouse->address->toHtml(', ', false) !!}
                        <p><em>{{ trans('theme.pickup_time') }} :</em></p>
                        @if (is_array($warehouse->business_days))
                          <i class="fa fa-calendar"></i> {{ implode(', ', $warehouse->business_days) }}<br />
                        @endif
                        @if ($warehouse->opening_time && $warehouse->close_time)
                          <i class="fa fa-clock-o"></i> {{ $warehouse->opening_time }} - {{ $warehouse->close_time }}
                        @endif
                        <input type="radio" class="warehouse_id" name="warehouse_id" value="{{ $warehouse->id }}">
                      </div>
                    </div>
                  @empty
                    <div class="col-sm-12">
                      <h4 class="my-3 text-info">{{ trans('theme.no_pickup_options') }}</h4>
                    </div>
                  @endforelse
                </div>
              </div>

              @if (isset($customer) && $customer)
                <div class="sf-checkout__address-head">
                  <h3 class="sf-checkout__section-title">{{ trans('theme.customer_address') }}</h3>
                  @if ($customer->addresses->count())
                    <button type="button" class="sf-checkout__change-addr" data-target="#sf-address-picker{{ $cart->id }}">
                      {{ trans('theme.change') }}
                    </button>
                  @endif
                </div>

                @if ($selectedAddress)
                  <div class="sf-checkout__current-addr address-list-item selected">
                    {!! $selectedAddress->toHtml('<br/>', false) !!}
                  </div>
                @endif

                <div class="row customer-address-list sf-checkout__addr-picker" id="sf-address-picker{{ $cart->id }}" style="{{ $selectedAddress ? 'display:none;' : '' }}">
                  @foreach ($customer->addresses as $address)
                    @php
                      $ship_to_this_address = $selectedAddress && $selectedAddress->id == $address->id;
                    @endphp
                    <div class="col-sm-12 col-md-6 textClass">
                      <div class="address-list-item {{ $ship_to_this_address ? 'selected' : '' }}">
                        {!! $address->toHtml('<br/>', false) !!}
                        <input type="radio" class="ship-to-address" name="ship_to" value="{{ $address->id }}" {{ $ship_to_this_address ? 'checked' : '' }} data-country="{{ $address->country_id }}" data-state="{{ $address->state_id }}" required>
                      </div>
                    </div>
                  @endforeach
                </div>

                <small id="ship-to-error-block" class="text-danger pull-right"></small>

                <div class="sf-checkout__add-addr">
                  <a href="{{ route('my.address.create') }}" class="modalAction btn btn-default btn-sm">
                    <i class="fas fa-address-card-o"></i> @lang('theme.button.add_new_address')
                  </a>
                </div>
              @else
                <div class="checkout-shiping-address">
                  @include('theme::partials.checkout_shiping_address')
                </div>
                @if ($cart->has_credit_rewards())
                  <span class="text-dark">
                    <i class="fa fa-warning"></i>
                    {{ trans('packages.wallet.create_an_account_to_get_reward') }}
                  </span>
                @endif
              @endif

              @if (is_incevio_package_loaded('pharmacy'))
                @include('pharmacy::checkout_form')
              @endif

              <div class="form-group mt-3">
                {!! Form::label('buyer_note', trans('theme.leave_message_to_seller'), ['class' => 'buyer_note']) !!}
                {!! Form::textarea('buyer_note', null, ['class' => 'form-control summernote-without-toolbar', 'placeholder' => trans('theme.placeholder.message_to_seller'), 'rows' => '3', 'maxlength' => '250']) !!}
                <div class="help-block with-errors"></div>
              </div>
            </div>
          </div>

          <div class="col-lg-4 px-3 py-3 sf-checkout__aside">
            <div class="side-widget" id="cart-summary{{ $cart->id }}">
              <h3 class="cart-summary-title"><span>{{ trans('theme.order_info') }}</span></h3>
              <ul class="shopping-cart-summary">
                <li>
                  <span>@lang('theme.cart_items')</span>
                  <span>{{ $cart->type }}</span>
                </li>
                <li>
                  <span>{{ trans('theme.subtotal') }}</span>
                  <span>
                    {{ get_currency_prefix() }}
                    <span id="summary-total{{ $cart->id }}" class="item-total{{ $cart->id }}">{{ number_format($cart_total, $dec, '.', '') }}</span>
                    {{ get_currency_suffix() }}
                  </span>
                </li>
                @unless ($cart->is_digital)
                  <li>
                    <span>
                      <a class="dynamic-shipping-rates" href="javascript:void(0);" data-toggle="popover" data-cart="{{ $cart->id }}" data-options="{{ $shipping_options[$cart->id] }}" id="shipping-options{{ $cart->id }}" title="{{ trans('theme.shipping') }}">
                        <u>{{ trans('theme.shipping') }}</u>
                      </a>
                      <em id="summary-shipping-name{{ $cart->id }}" class="small text-muted"></em>
                    </span>
                    <span>{{ get_currency_prefix() }}
                      <span id="summary-shipping{{ $cart->id }}">{{ number_format($cart->get_shipping_cost(), $dec, '.', '') }}</span>{{ get_currency_suffix() }}
                    </span>
                  </li>
                  @if (is_incevio_package_loaded('packaging') && !empty(json_decode($packaging_options)))
                    <li>
                      <span>
                        <a class="packaging-options" href="javascript:void(0);" data-toggle="popover" data-cart="{{ $cart->id }}" data-options="{{ $packaging_options }}" title="{{ trans('theme.packaging') }}">
                          <u>{{ trans('theme.packaging') }}</u>
                        </a>
                        <em class="small text-muted" id="summary-packaging-name{{ $cart->id }}">
                          {{ $default_packaging ? $default_packaging->name : '' }}
                        </em>
                      </span>
                      <span>{{ get_currency_prefix() }}
                        <span id="summary-packaging{{ $cart->id }}">
                          {{ number_format($default_packaging ? get_formated_price_value($default_packaging->cost) : 0, $dec, '.', '') }}
                        </span>{{ get_currency_suffix() }}
                      </span>
                    </li>
                  @endif
                @endunless
                <li id="discount-section-li{{ $cart->id }}" style="display: {{ $cart->coupon ? 'block' : 'none' }};">
                  <span>{{ trans('theme.discount') }}
                    <em id="summary-discount-name{{ $cart->id }}" class="small text-muted">{{ $cart->coupon ? $cart->coupon->name . ' (' . $cart->coupon->getFormatedAmountText() . ')' : '' }}</em>
                  </span>
                  <span>-{{ get_currency_prefix() }}
                    <span id="summary-discount{{ $cart->id }}">{{ $cart->coupon ? number_format($cart->discount, $dec, '.', '') : number_format(0, $dec, '.', '') }}</span>{{ get_currency_suffix() }}
                  </span>
                </li>
                <li id="tax-section-li{{ $cart->id }}" style="{{ $cart->taxes ? '' : 'display: none' }};">
                  <span>{{ trans('theme.taxes') }}</span>
                  <span>{{ get_currency_prefix() }}
                    <span id="summary-taxes{{ $cart->id }}">{{ number_format($cart->taxes, $dec, '.', '') }}</span>{{ get_currency_suffix() }}
                  </span>
                </li>
                <li>
                  <span>{{ trans('theme.total') }}</span>
                  <span>{{ get_currency_prefix() }}
                    <span id="summary-grand-total{{ $cart->id }}">{{ number_format(get_formated_value($cart->grand_total), $dec, '.', '') }}</span>{{ get_currency_suffix() }}
                  </span>
                </li>
                <li id="checkout-summary-customer-fee-li{{ $cart->id }}" style="display: none;">
                  <span>{{ trans('packages.wallet.checkout_customer_platform_fee') }}</span>
                  <span id="checkout-summary-customer-fee{{ $cart->id }}">—</span>
                </li>
                <li id="checkout-summary-pay-total-li{{ $cart->id }}" style="display: none;">
                  <span><strong>{{ trans('packages.wallet.checkout_you_will_pay') }}</strong></span>
                  <span><strong id="checkout-summary-pay-total{{ $cart->id }}">—</strong></span>
                </li>
              </ul>
            </div>

            <div class="cart-payment-options sf-checkout__pay">
              @if (allow_checkout())
                @include('partials.payment_options', ['shop' => $shop, 'cart' => $cart, 'customer' => $customer, 'paymentMethods' => $cartPaymentMethods])
              @elseif (is_panel_user_on_storefront())
                <button type="button" class="btn btn-primary btn-block" disabled title="{{ panel_user_storefront_message() }}">
                  {{ trans('theme.notify.panel_user_order_restricted') }}
                </button>
              @else
                <a href="#nav-login-dialog" data-toggle="modal" data-target="#loginModal" class="btn btn-primary btn-block">
                  {{ trans('theme.button.login') }}
                </a>
              @endif
            </div>

            @if ($trust_badge = get_trust_badge_url())
              <div class="text-center my-4">
                <img src="{{ $trust_badge }}" alt="{{ trans('theme.trust_badge') }}"/>
              </div>
            @endif

            <a class="btn btn-default btn-block" href="{{ url('/') }}">{{ trans('theme.button.continue_shopping') }}</a>
          </div>
        </div>

        {!! Form::close() !!}

        @if (config('services.google.gtm_container_id'))
          @include('scripts.dataLayer.cart_page')
        @endif
      @endforeach
    @else
      <div class="row">
        <div class="col-12">
          <p class="lead text-center my-5">
            {{ trans('theme.empty_cart') }}<br /><br />
            <a href="{{ url('/') }}" class="btn btn-primary">
              <i class="fas fa-shopping-cart no-fill"></i> @lang('theme.button.shop_now')
            </a>
          </p>
        </div>
      </div>
    @endif
  </div>
</section>
