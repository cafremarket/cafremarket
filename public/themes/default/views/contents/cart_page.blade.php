<section class="sf-checkout px-xl-0 px-3 mt-3 mt-lg-0">
  <div class="container lg-100">
    @php
      $dec = is_non_decimal_currency() ? 0 : config('system_settings.decimals', 2);
      $isMultiStore = $carts->count() > 1;
      $primaryCart = $activeCart ?? $carts->first();
      $combinedSubtotal = 0;
      $combinedGrand = 0;
      $anyBlocked = false;
      foreach ($carts as $c) {
          $combinedGrand += (float) $c->grand_total;
          if (!empty($c->out_of_range) || !empty($c->needs_delivery_location)) {
              $anyBlocked = true;
          }
      }
      // Payment methods: platform list when multi-store (single combined charge).
      $checkoutShop = $primaryCart ? $primaryCart->shop : null;
      $cartPaymentMethods = ($isMultiStore || ! vendor_get_paid_directly())
          ? $paymentMethods
          : ($checkoutShop->paymentMethods ?? $paymentMethods);

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
              } elseif ($primaryCart && $primaryCart->ship_to && $primaryCart->ship_to == $address->id) {
                  $pre_select = $address;
              } elseif ($primaryCart && $primaryCart->ship_to_country_id == $address->country_id && $primaryCart->ship_to_state_id == $address->state_id) {
                  $pre_select = $address;
              } elseif ($primaryCart && $primaryCart->ship_to == null && $address->address_type === 'Shipping') {
                  $pre_select = $address;
              }
          }
          $selectedAddress = $pre_select ?: $customer->addresses->first();
      }
    @endphp

    @if (Session::has('error'))
      <div class="notice notice-danger notice-sm mb-3">
        <strong>{{ trans('theme.error') }}</strong> {{ Session::get('error') }}
      </div>
    @endif

    @if ($carts->count() > 0)
      <header class="sf-checkout__intro">
        <h1>{{ trans('theme.checkout') }}</h1>
        <p>
          {{ trans('theme.shopping_cart') }}
          @if ($isMultiStore)
            — {{ $carts->count() }} {{ strtolower(trans('theme.stores')) }}
          @endif
        </p>
      </header>

      @if ($isMultiStore)
        {!! Form::open(['route' => 'order.createAll', 'id' => 'formIdCheckoutAll', 'name' => 'checkoutForm', 'files' => true, 'data-toggle' => 'validator', 'autocomplete' => 'off', 'novalidate', 'class' => 'sf-checkout__form']) !!}
      @else
        {!! Form::open(['route' => ['order.create', $primaryCart], 'id' => 'formId' . $primaryCart->id, 'name' => 'checkoutForm', 'files' => true, 'data-toggle' => 'validator', 'autocomplete' => 'off', 'novalidate', 'class' => 'sf-checkout__form']) !!}
        {{ Form::hidden('cart_id', $primaryCart->id, ['id' => 'checkout-id']) }}
      @endif

      <div class="row sf-checkout__layout">
        <div class="col-lg-8 px-3">
          {{-- Continuous store list: Store name & details → products → next store --}}
          @foreach ($carts as $cart)
            <div class="sf-checkout__card mb-3">
              @include('theme::partials._checkout_store_block', [
                'cart' => $cart,
                'shipping_zones' => $shipping_zones,
                'platformDefaultPackaging' => $platformDefaultPackaging ?? null,
                'dec' => $dec,
              ])
            </div>
          @endforeach

          <div class="sf-checkout__card mb-3 px-3 py-3">
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
              @if ($primaryCart && $primaryCart->is_digital && ! $isMultiStore)
                <h3 class="sf-checkout__section-title">{{ trans('theme.billing_address') }}</h3>
              @else
                <div class="sf-checkout__fulfilment">
                  <label class="sf-checkout__fulfil-opt">
                    <input class="form-check-input" type="radio" name="fulfilment_type" id="fulfilment_type_deliver" value="{{ \App\Models\Order::FULFILMENT_TYPE_DELIVER }}" checked>
                    <span><i class="far fa-shipping-fast"></i> {{ trans('theme.ship_to') }}</span>
                  </label>
                </div>
              @endif

              @if (isset($customer) && $customer)
                <div class="sf-checkout__address-head">
                  <h3 class="sf-checkout__section-title">{{ trans('theme.customer_address') }}</h3>
                  @if ($customer->addresses->count())
                    <button type="button" class="sf-checkout__change-addr" data-target="#sf-address-picker-shared">
                      {{ trans('theme.change') }}
                    </button>
                  @endif
                </div>

                @if ($selectedAddress)
                  <div class="sf-checkout__current-addr address-list-item selected">
                    {!! $selectedAddress->toHtml('<br/>', false) !!}
                  </div>
                @endif

                <div class="row customer-address-list sf-checkout__addr-picker" id="sf-address-picker-shared" style="{{ $selectedAddress ? 'display:none;' : '' }}">
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
        </div>

        <div class="col-lg-4 px-3 sf-checkout__aside">
          <div class="side-widget sf-checkout__card" id="cart-summary-combined">
            <h3 class="cart-summary-title"><span>{{ trans('theme.order_info') }}</span></h3>
            <ul class="shopping-cart-summary">
              @foreach ($carts as $sumCart)
                <li>
                  <span>{{ optional($sumCart->shop)->name ?? ('Store #'.$sumCart->shop_id) }}</span>
                  <span>{{ get_formated_currency($sumCart->grand_total, 2) }}</span>
                </li>
              @endforeach
              <li>
                <span><strong>{{ trans('theme.total') }}</strong></span>
                <span>
                  <strong>
                    {{ get_currency_prefix() }}
                    <span id="summary-grand-total-combined" data-value="{{ get_formated_value($combinedGrand) }}">{{ get_formated_decimal(get_formated_value($combinedGrand), false, $dec) }}</span>
                    {{ get_currency_suffix() }}
                  </strong>
                </span>
              </li>
            </ul>
          </div>

          <div class="cart-payment-options sf-checkout__pay sf-checkout__card mt-3 p-3">
            @if ($anyBlocked)
              <button type="button" class="btn btn-danger btn-block" disabled>
                {{ trans('theme.notify.set_location_for_delivery') ?: 'Set your delivery location' }}
              </button>
            @elseif (allow_checkout())
              @include('partials.payment_options', [
                'shop' => $checkoutShop,
                'cart' => $primaryCart,
                'customer' => $customer,
                'paymentMethods' => $cartPaymentMethods,
              ])
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

          <a class="btn btn-default btn-block mt-2" href="{{ url('/') }}">{{ trans('theme.button.continue_shopping') }}</a>
        </div>
      </div>

      {!! Form::close() !!}

      @if (config('services.google.gtm_container_id'))
        @include('scripts.dataLayer.cart_page')
      @endif
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
