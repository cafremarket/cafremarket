@extends('admin.layouts.master')

@can('create', \App\Models\Order::class)
  @section('buttons')
    <a href="javascript:void(0)" data-link="{{ route('admin.order.order.searchCustomer') }}" class="ajax-modal-btn btn btn-new btn-flat">{{ trans('app.search_again') }}</a>

    <a href="{{ route('admin.order.order.index') }}" class="btn btn-new btn-flat">{{ trans('app.cancel') }}</a>
  @endsection
@endcan

@section('content')
  @php
    $shipping_address = $customer->shippingAddress ? $customer->shippingAddress : ($customer->primaryAddress ?: $addresses->first());
    $billing_address = $customer->billingAddress ? $customer->billingAddress : $shipping_address;

    // Pre-select the addresses saved on the cart (if any)
    if (isset($cart->shipping_address) && $addresses->firstWhere('id', $cart->shipping_address)) {
        $shipping_address = $addresses->firstWhere('id', $cart->shipping_address);
    }
    if (isset($cart->billing_address) && $addresses->firstWhere('id', $cart->billing_address)) {
        $billing_address = $addresses->firstWhere('id', $cart->billing_address);
    }

    $address_options = $addresses->mapWithKeys(function ($address) {
        $label = trim(($address->address_title ? $address->address_title . ' - ' : '') . $address->toString());
        return [$address->id => '[' . strtoupper($address->address_type) . '] ' . $label];
    });
    $shipping_zone = $shipping_address ? get_shipping_zone_of(Auth::user()->merchantId(), $shipping_address->country_id, $shipping_address->state_id) : null;

    $shipping_options = isset($shipping_zone->id) ? getShippingRates($shipping_zone->id) : 'NaN';

    if (is_incevio_package_loaded('packaging')) {
        $packaging_options = getPackagings();
        $default_packaging = isset($cart->packaging_id) ? $cart->shippingPackage : getDefaultPackaging();
    }
  @endphp

  <div class="row">
    @if (count($cart_lists))
      @can('index', \App\Models\Cart::class)
        <div class="col-md-12">
          @include('admin.partials._cart_list')
        </div>
      @endcan
    @endif

    {!! Form::open(['route' => 'admin.order.order.store', 'files' => true, 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="col-md-9">
      @include('admin.partials.ui.card_start', [
        'title' => trans('app.cart'),
        'icon' => 'fa-shopping-cart',
        'class' => 'admin-form-section',
        'bodyClass' => '',
      ])
          {{ Form::hidden('customer_id', $customer->id) }}
          {{ Form::hidden('discount', isset($cart->discount) ? $cart->discount : null, ['id' => 'cart-discount']) }}
          {{ Form::hidden('taxrate', null, ['id' => 'cart-taxrate']) }}
          {{ Form::hidden('taxes', null, ['id' => 'cart-taxes']) }}

          @if (is_incevio_package_loaded('packaging'))
            {{ Form::hidden('packaging_id', $default_packaging ? $default_packaging->id : null, ['id' => 'packaging_id']) }}
            {{ Form::hidden('packaging', $default_packaging ? $default_packaging->cost : null, ['id' => 'cart-packaging']) }}
          @endif

          {{ Form::hidden('shipping', null, ['id' => 'cart-shipping']) }}
          {{ Form::hidden('shipping_zone_id', isset($shipping_zone->id) ? $shipping_zone->id : null, ['id' => 'shipping_zone_id']) }}
          {{ Form::hidden('shipping_rate_id', null, ['id' => 'shipping_rate_id']) }}

          @include('admin.order._add_to_cart')

          @include('admin.order._cart')

          <hr class="style7">

          <div class="row">
            <div class="col-md-6">
              <dir class="spacer30"></dir>
              <div class="form-group">
                {!! Form::label('admin_note', trans('app.form.admin_note'), ['class' => 'with-help']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.admin_note') }}"></i>
                {!! Form::textarea('admin_note', isset($cart->admin_note) ? $cart->admin_note : null, ['class' => 'form-control summernote-without-toolbar', 'rows' => '2', 'placeholder' => trans('app.placeholder.admin_note')]) !!}
              </div>
            </div>
            <div class="col-md-6" id="summary-block">
              <table class="table">
                <tr>
                  <td class="text-right">{{ trans('app.total') }}</td>
                  <td class="text-right" width="40%">{{ get_currency_prefix() }}
                    <span id="summary-total" data-value="0">{{ get_formated_decimal(0, true, 2) }}</span>{{ get_currency_suffix() }}
                  </td>
                </tr>

                <tr>
                  <td class="text-right">
                    <a class="discount-options" data-toggle="popover" title="{{ trans('app.discount') }}">
                      <u>{{ trans('app.discount') }}</u>
                    </a>
                  </td>
                  <td class="text-right" width="40%"> &minus; {{ get_currency_prefix() }}
                    <span id="summary-discount" data-value="{{ isset($cart->discount) ? $cart->discount : 0 }}">
                      {{ isset($cart->discount) ? get_formated_decimal($cart->discount, true, 2) : get_formated_decimal(0, true, 2) }}
                    </span>{{ get_currency_suffix() }}
                  </td>
                </tr>

                <tr>
                  <td class="text-right">
                    <a class="dynamic-shipping-rates" data-toggle="popover" title="{{ trans('app.shipping') }}">
                      <u>{{ trans('app.shipping') }}</u></a><br />
                    <em id="summary-shipping-name" class="small"></em>
                  </td>
                  <td class="text-right" width="40%">{{ get_currency_prefix() }}
                    <span id="summary-shipping" data-value="0">{{ get_formated_decimal(0, true, 2) }}</span>{{ get_currency_suffix() }}
                  </td>
                </tr>

                @if (is_incevio_package_loaded('packaging'))
                  <tr>
                    <td class="text-right">
                      <a class="packaging-options" data-toggle="popover" title="{{ trans('app.packaging') }}">
                        <u>{{ trans('app.packaging') }}</u></a><br />
                      <em id="summary-packaging-name" class="small">{{ $default_packaging ? $default_packaging->name : '' }}</em>
                    </td>
                    <td class="text-right" width="40%">{{ get_currency_prefix() }}
                      <span id="summary-packaging" data-value="{{ $default_packaging ? $default_packaging->cost : 0 }}">
                        {{ get_formated_decimal($default_packaging ? $default_packaging->cost : 0, true, 2) }}
                      </span>{{ get_currency_suffix() }}
                    </td>
                  </tr>
                @endif

                @if ((bool) get_formated_decimal(config('shop_settings.order_handling_cost')))
                  <tr>
                    <td class="text-right">{{ trans('app.handling') }}</td>
                    <td class="text-right" width="40%">{{ get_currency_prefix() }}
                      <span id="summary-handling" data-value="{{ config('shop_settings.order_handling_cost') }}">{{ get_formated_decimal(config('shop_settings.order_handling_cost'), true, 2) }}</span>{{ get_currency_suffix() }}
                    </td>
                  </tr>
                @endif

                <tr>
                  <td class="text-right">{{ trans('app.taxes') }} <br />
                    <em class="small">
                      <span id="summary-zone-name">{{ isset($shipping_zone->name) ? $shipping_zone->name . ' ' : '' }}</span>
                      <span id="summary-taxrate"></span>%
                      </small>
                  </td>
                  <td class="text-right" width="40%">{{ get_currency_prefix() }}
                    <span id="summary-tax" data-value="0">{{ get_formated_decimal(0, true, 2) }}</span>{{ get_currency_suffix() }}
                  </td>
                </tr>

                <tr class="lead">
                  <td class="text-right">{{ trans('app.grand_total') }}</td>
                  <td class="text-right" width="40%">{{ get_currency_prefix() }}
                    <span id="summary-grand-total" data-value="0">{{ get_formated_decimal(0, true, 2) }}</span>{{ get_currency_suffix() }}
                  </td>
                </tr>
              </table>
            </div>
          </div>
          <p class="help-block">* {{ trans('app.form.required_fields') }}</p>
      @include('admin.partials.ui.card_end')

      <div class="admin-card admin-card--footer-only admin-form-section">
        <div class="admin-card__body">
          @if (isset($cart))
            {{ Form::hidden('cart_id', $cart->id, ['id' => 'cart_id']) }}
            @unless (isset($order_cart))
              <small>
                {!! Form::checkbox('delete_the_cart', 1, null, ['class' => 'icheck', 'checked']) !!}
                {!! Form::label('delete_the_cart', strtoupper(trans('app.delete_the_cart')), ['class' => 'indent5']) !!}
                <i class="fa fa-question-circle indent5" data-toggle="tooltip" data-placement="top" title="{{ trans('help.delete_the_cart') }}"></i>
              </small>
            @endunless
          @endif

          <div class="box-tools pull-right admin-card__actions">
            @if (Gate::allows('create', \App\Models\Cart::class) || Gate::allows('create', \App\Models\Order::class) || Gate::allows('update', \App\Models\Cart::class))
              <button name='action' value="1" id="saveTheCart" class='btn btn-flat btn-lg btn-default'>
                <i class="fa fa-save"></i>
                @if (isset($order_cart))
                  {{ trans('app.update_the_order') }}
                @elseif(isset($cart))
                  {{ trans('app.update_n_back') }}
                @else
                  {{ trans('app.save_n_back') }}
                @endif
              </button>
            @endif

            @if (!isset($order_cart) && Gate::allows('create', \App\Models\Order::class))
              <button name='action' type="submit" id="place-order-btn" class='btn btn-flat btn-lg btn-new' @if ($shipping_options == 'NaN') style="display: none;" @endif>
                {{ trans('app.place_order') }}
              </button>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3 nopadding-left">
      @include('admin.partials.ui.card_start', [
        'title' => trans('app.customer'),
        'icon' => 'fa-user',
        'class' => 'admin-form-section',
        'bodyClass' => '',
      ])
          <p>
            @if ($customer->image)
              <img src="{{ get_storage_file_url(optional($customer->image)->path, 'tiny') }}" class="img-circle img-sm" alt="{{ trans('app.avatar') }}">
            @else
              <img src="{{ get_gravatar_url($customer->email, 'tiny') }}" class="img-circle img-sm" alt="{{ trans('app.avatar') }}">
            @endif

            <span class="admin-user-widget-title indent5">
              {{ $customer->getName() }}
            </span>
          </p>

          <span class="admin-user-widget-text text-muted">
            {{ trans('app.email') . ': ' . $customer->email }}
          </span>
          @can('view', $customer)
            <a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.show', $customer->id) }}" class="ajax-modal-btn btn btn-default btn-xs">{{ trans('app.view_detail') }}</a>
          @endcan
      @include('admin.partials.ui.card_end')

      @include('admin.partials.ui.card_start', [
        'title' => trans('app.addresses'),
        'icon' => 'fa-map-marker',
        'class' => 'admin-form-section',
        'bodyClass' => '',
      ])
          @if ($addresses->isEmpty())
            <p class="text-muted">{{ trans('messages.notice.no_billing_address') }}</p>
            <a href="javascript:void(0)" data-link="{{ route('address.create', ['customer', $customer->id]) }}" class="ajax-modal-btn btn btn-new"><i class="fa fa-plus-square-o"></i> {{ trans('app.add_address') }} </a>
          @else
            <fieldset>
              <legend>{{ strtoupper(trans('app.shipping_address')) }}</legend>
            </fieldset>
            <div class="form-group">
              {!! Form::select('shipping_address', $address_options, optional($shipping_address)->id, ['id' => 'shipping-address-select', 'class' => 'form-control select2-normal', 'required']) !!}
            </div>
            <div class="address-preview well well-sm" id="shipping-address-preview">
              {!! optional($shipping_address)->toHtml('<br/>', false) !!}
            </div>

            <fieldset>
              <legend>{{ strtoupper(trans('app.billing_address')) }}</legend>
            </fieldset>
            <small>
              {!! Form::checkbox('same_as_shipping_address', 1, null, ['id' => 'same_as_shipping_address', 'class' => 'icheck']) !!}
              {!! Form::label('same_as_shipping_address', strtoupper(trans('app.same_as_shipping_address')), ['class' => 'indent5']) !!}
            </small>

            <div class="spacer20"></div>

            <div id="billing-address-block">
              <div class="form-group">
                {!! Form::select('billing_address', $address_options, optional($billing_address)->id, ['id' => 'billing-address-select', 'class' => 'form-control select2-normal', 'required']) !!}
              </div>
              <div class="address-preview well well-sm" id="billing-address-preview">
                {!! optional($billing_address)->toHtml('<br/>', false) !!}
              </div>
            </div>

            <a href="javascript:void(0)" data-link="{{ route('address.create', ['customer', $customer->id]) }}" class="ajax-modal-btn btn btn-default btn-xs"><i class="fa fa-plus-square-o"></i> {{ trans('app.add_address') }} </a>
          @endif
      @include('admin.partials.ui.card_end')

      @include('admin.partials.ui.card_start', [
        'title' => trans('app.payment'),
        'icon' => 'fa-credit-card',
        'class' => 'admin-form-section',
        'bodyClass' => '',
      ])
          <div class="form-group">
            {!! Form::label('payment_method_id', trans('app.form.payment_method') . '*') !!}
            {!! Form::select('payment_method_id', $payment_methods, isset($cart->payment_method_id) ? $cart->payment_method_id : config('shop_settings.default_payment_method_id'), ['class' => 'form-control select2-normal', 'placeholder' => trans('app.placeholder.payment'), 'required']) !!}
            <div class="help-block with-errors"></div>
          </div>
          <div class="form-group">
            {!! Form::label('payment_status', trans('app.form.payment_status') . '*') !!}
            {!! Form::select('payment_status', $payment_statuses, isset($cart->payment_status) ? $cart->payment_status : 1, ['class' => 'form-control select2-normal', 'required']) !!}
            <div class="help-block with-errors"></div>
          </div>
      @include('admin.partials.ui.card_end')

      @include('admin.partials.ui.card_start', [
        'title' => trans('app.invoice'),
        'icon' => 'fa-file-text-o',
        'class' => 'admin-form-section',
        'bodyClass' => '',
      ])
          <div class="form-group">
            {!! Form::label('message_to_customer', trans('app.form.message_to_customer'), ['class' => 'with-help']) !!}
            <i class="fa fa-question-circle indent5" data-toggle="tooltip" data-placement="top" title="{{ trans('help.message_to_customer') }}"></i>
            {!! Form::textarea('message_to_customer', isset($cart->message_to_customer) ? $cart->message_to_customer : null, ['class' => 'form-control summernote-without-toolbar', 'rows' => '2', 'placeholder' => trans('app.placeholder.message_to_customer')]) !!}
          </div>
          <small>
            {!! Form::checkbox('send_invoice_to_customer', 1, null, ['class' => 'icheck', 'checked']) !!}
            {!! Form::label('send_invoice_to_customer', strtoupper(trans('app.send_invoice_to_customer')), ['class' => 'indent5']) !!}
            <i class="fa fa-question-circle indent5" data-toggle="tooltip" data-placement="top" title="{{ trans('help.send_invoice_to_customer') }}"></i>
          </small>
      @include('admin.partials.ui.card_end')
    </div>
    {!! Form::close() !!}
  </div>
@endsection

@section('page-script')
  <style type="text/css">
    #summary-block a {
      cursor: pointer;
    }
  </style>

  <script language="javascript" type="text/javascript">
    ;
    (function($, window, document) {
      var payment_methods = <?= $payment_methods ?>;
      if (payment_methods.length == 0) {
        $("#global-alert-msg").html('{!! trans('messages.notice.no_active_payment_method') . ' ' . '<a href="' . route('admin.setting.config.paymentMethod.index') . '">' . trans('app.activate') . '</a>' !!}');
        $("#global-alert-box").removeClass('hidden');
      }

      var billing_address = {{ $billing_address ? 'true' : 'false' }};
      if (!billing_address) {
        $("#global-alert-msg").html('{!! trans('messages.notice.no_billing_address') . ' ' . '<a class="ajax-modal-btn btn btn-new" href="javascript:void(0)" data-link="' . route('address.create', ['customer', $customer->id]) . '"><i class="fa fa-plus-square-o"></i>' . trans('app.add_address') . '</a>' !!}');
        $("#global-alert-box").removeClass('hidden');
      }

      var cartWeight = 0;

      @if (is_incevio_package_loaded('packaging'))
        var packaging_options = <?= $packaging_options ?>;
      @endif

      var shipping_options = <?= $shipping_options ?>;
      var productObj = <?= json_encode($inventories) ?>;

      var cart = "{{ isset($cart) ? true : false }}";
      if (cart) {
        @if (is_incevio_package_loaded('packaging'))
          setPackagingCost('{{ $default_packaging->name }}', {{ $default_packaging->cost }}, {{ $default_packaging->id }});
        @endif

        calculateOrderTotal();
      }

      // Set default settings based on shop and system configs
      var taxId = "{{ isset($shipping_zone->tax_id) ? $shipping_zone->tax_id : config('shop_settings.default_tax_id') }}";
      if (taxId) {
        setTax(taxId);
      }

      if (!shipping_options) {
        $("#global-alert-msg").html('{{ trans('messages.notice.no_shipping_option_for_the_zone') }}');
        $("#global-alert-box").removeClass('hidden');
      } else if ($.isEmptyObject(shipping_options)) {
        $("#global-alert-msg").html('{!! trans('messages.notice.no_rate_for_the_shipping_zone', ['zone' => optional($shipping_zone)->name]) !!}');
        $("#global-alert-box").removeClass('hidden');
      }

      var apply_btn = '<div class="spacer5"></div><button class="popover-submit-btn btn btn-flat btn-new btn-lg btn-block" type="button">{{ trans('app.apply') }}</button>';

      // Do appropriate actions and Update order detail
      $(document).on("click", ".popover-submit-btn", function() {
        var node = $(this).parents('.popover-form');
        var nodeId = node.attr('id');

        switch (nodeId) {
          case 'shipping-options-popover':
            var shipping = $('input[name=shipping_option]:checked');
            var name = shipping.attr('id') == 'custom_shipping' ? '{{ trans('app.custom_shipping') }}' : shipping.attr('id');
            var value = shipping.val();
            var id = shipping.parent('label').attr('id');
            setShippingCost(name, value, id);
            break;

          case 'packaging-options-popover':
            var packaging = $('input[name=packaging_option]:checked');
            var id = packaging.parent('label').attr('id');
            setPackagingCost(packaging.attr('id'), packaging.val(), id);
            break;

          case 'discount-options-popover':
            setDiscount(node.find('input#input-discount').val());
            break;
        }
      });

      $('a.discount-options').popover({
        html: true,
        placement: 'left',
        content: function() {
          var current = getDiscount();

          var options = '<div class="input-group" id="discount-popover"><span class="input-group-addon">{{ get_formated_currency_symbol() }}</span><input id="input-discount" name="discount" class="form-control" value="' + current + '" type="number" step="any" placeholder = {{ trans('app.discount') }}></div>';

          return '<div class="popover-form" id="discount-options-popover">' +
            options + apply_btn +
            '</div>';
        }
      });

      $('a.packaging-options').popover({
        html: true,
        placement: 'left',
        content: function() {
          var current = getPackagingName();

          var options = '';
          packaging_options.forEach(function(item) {
            var preChecked = String(current) == String(item.name) ? 'checked' : '';

            options += '<div class="radio"><label id="' + item.id + '"><input type="radio" name="packaging_option" id="' + item.name + '" value="' + getFormatedValue(item.cost) + '" ' + preChecked + '>' + item.name + '</label></div>';
          });

          return '<div class="popover-form" id="packaging-options-popover">' +
            options + apply_btn +
            '</div>';
        }
      });

      $('a.dynamic-shipping-rates').popover({
        html: true,
        placement: 'left',
        content: function() {
          var current = getShippingName();
          var preChecked = String(current) == '{{ trans('app.custom_shipping') }}' ? 'checked' : '';
          var custValue = preChecked == 'checked' ? getShipping() : '';

          var custom_shipping = '<div class="radio"><label id=""><input type="radio" name="shipping_option" id="custom_shipping"' + preChecked + '>{{ trans('app.custom_shipping') }}</label></div>' +
            '<div class="input-group"><span class="input-group-addon">{{ get_formated_currency_symbol() }}</span><input id="input-shipping" name="custom_shipping" class="form-control" value="' + custValue + '" type="number" step="any" placeholder = {{ trans('app.placeholder.custom_shipping') }}></div>';

          var filtered = getShippingOptions();

          var options = '';
          filtered.forEach(function(item) {
            var preChecked = String(current) == String(item.name) ? 'checked' : '';

            options += '<div class="radio"><label id="' + item.id + '"><input type="radio" name="shipping_option" id="' + item.name + '" value="' + getFormatedValue(item.rate) + '" ' + preChecked + '>' + item.name + '</label></div>';
          });

          return '<div class="popover-form" id="shipping-options-popover">' +
            options + custom_shipping + apply_btn +
            '</div>';
        }
      });

      $('body').on('focus change', 'input#input-shipping', function() {
        $("input:radio#custom_shipping").prop("checked", true).val($(this).val());
      });

      $('body').on('change', '.itemQtt, .itemPrice', function() {
        var itemId = $(this).closest('tr').attr('id');
        calculateItemTotal(itemId);
      });

      $('body').on('click', '.deleteThisRow', function() {
        var itemId = $(this).closest('tr').attr('id');
        deleteThisRow(itemId);
      });

      // Add to Cart
      $('#add-to-cart-btn').click(
        function() {
          var ID = $("#product-to-add").select2('data')[0].id;
          var itemDescription = $("#product-to-add").select2('data')[0].text;

          if (ID == '' || itemDescription == '') {
            return false;
          } else {
            $("#empty-cart").hide(); // Hide the empty cart message
          }

          $("#product-to-add").select2("val", ""); // Reset the product search dropdown

          // Check if the product is already on the cart, Is so then just increase the qtt
          if ($("tr#" + ID).length) {
            increaseQttByOne(ID);
            calculateItemTotal(ID);
            return;
          }

          //Pick the string after the : to get the item description
          itemDescription = itemDescription.substring(itemDescription.indexOf(":") + 2);

          var imgSrc = getFromPHPHelper('get_product_img_src', ID, 'tiny');

          var numOfRows = $("tbody#items tr").length;

          var node = '<tr id="' + ID + '">' +
            '<td><img src="' + imgSrc + '" class="img-circle img-md" alt="{{ trans('app.image') }}"></td>' +
            '<td class="nopadding-right" width="55%">' + itemDescription +
            '<input type="hidden" name="cart[' + numOfRows + '][inventory_id]" value="' + ID + '"></input>' +
            '<input type="hidden" name="cart[' + numOfRows + '][item_description]" value="' + itemDescription + '"></input>' +
            '<input type="hidden" name="cart[' + numOfRows + '][shipping_weight]" value="' + productObj[ID].shipping_weight + '" id="weight-' + ID + '" class="itemWeight"></input>' +
            '</td>' +
            '<td class="nopadding-right" width="15%">' +
            '<input name="cart[' + numOfRows + '][unit_price]" value="' + productObj[ID].salePrice + '" id="price-' + ID + '" type="number" class="form-control itemPrice no-border" placeholder="{{ trans('app.price') }}" required>' +
            '</div>' +
            '<td>x</td>' +
            '<td class="nopadding-right" width="10%">' +
            '<input name="cart[' + numOfRows + '][quantity]" value="1" type="number" id="qtt-' + ID + '" class="form-control itemQtt no-border" placeholder="{{ trans('app.quantity') }}" required>' +
            '</td>' +
            '<td class="nopadding-right text-center" width="10%">{{ get_currency_prefix() }}' +
            '<span id="total-' + ID + '" class="itemTotal" data-value="' + productObj[ID].salePrice + '">' +
            getFormatedNumber(productObj[ID].salePrice) +
            '</span>{{ get_currency_suffix() }}' +
            '</td>' +
            '<td class="small"><i class="fa fa-trash text-muted deleteThisRow" data-toggle="tooltip" data-placement="left" title="{{ trans('help.remove_this_cart_item') }}"></i></td>' +
            '</tr>';

          $('tbody#items').append(node);

          calculateOrderTotal();

          return false; //Return false to prevent unspected form submition
        }
      );

      function calculateItemTotal(ID) {
        // var itemTotal = getItemTotal(ID);
        var itemWeight = getItemTotalWeight(ID);
        var itemTotal = getItemTotal(ID);

        $("#weight-" + ID).val(itemWeight);
        $("#total-" + ID).data('value', itemTotal).text(getFormatedNumber(itemTotal));

        calculateOrderTotal();
        return;
      };

      function getShippingOptions() {
        var totalPrice = getOrderTotal();
        var totalWeight = cartWeight;

        var filtered = shipping_options.filter(function(el) {
          var result = el.based_on == 'price' &&
            el.minimum <= totalPrice &&
            (el.maximum >= totalPrice || !el.maximum);

          if (totalWeight) {
            result = result ||
              (el.based_on == 'weight' &&
                el.minimum <= totalWeight &&
                el.maximum >= totalWeight);
          }

          return result;
        });

        return filtered;
      }

      /**
       * This function will need in front end
       */

      function calculateOrderTotal() {
        cartWeight = 0;
        var sum = 0;
        $(".itemTotal").each(
          function() {
            sum += Number($(this).data('value'));
          }
        );
        $("#summary-total").data('value', sum).text(getFormatedNumber(sum));

        $(".itemWeight").each(function() {
          cartWeight += ($(this).val()) * 1;
        });

        if (!cartWeight) {
          $("#global-alert-msg").html('{{ trans('messages.notice.cant_cal_weight_shipping_rate') }}');
          $("#global-alert-box").removeClass('hidden');
        }

        var options = getShippingOptions();

        if (options[0]) {
          setShippingCost(options[0].name, options[0].rate, options[0].id);
        } else {
          setShippingCost('');
        }

        return;
      };

      function setDiscount(value = 0) {
        $('#summary-discount').data('value', value).text(getFormatedNumber(value));
        $('#cart-discount').val(value);
        calculateTax();
        return;
      }

      function setShippingCost(name = '', value = 0, id = '') {
        value = value ? value : 0;
        $('#summary-shipping').data('value', value).text(getFormatedNumber(value));
        $("#summary-shipping-name").text(name);
        $('#cart-shipping').val(value);
        $('#shipping_rate_id').val(id);
        calculateTax();
        return;
      }

      function setPackagingCost(name, value = 0, id = '') {
        value = value ? value : 0;
        $('#summary-packaging').data('value', value).text(getFormatedNumber(value));
        $("#summary-packaging-name").text(name);
        $('#cart-packaging').val(value);
        $('#packaging_id').val(id);
        calculateTax();
        return;
      }

      function setTax(ID = NULL) {
        if (!ID) {
          $("#summary-taxrate").text(0);
          calculateTax();
          return;
        }

        $.ajax({
          data: "ID=" + ID,
          url: "{{ route('ajax.getTaxRate') }}",
          success: function(result) {
            $("#summary-taxrate").text(result);
            $("#cart-taxrate").val(result);
            calculateTax();
          }
        });
        return;
      }

      function calculateTax() {
        var total = getTotalAmount();
        var taxrate = getTaxrate();

        var tax = (total * taxrate) / 100;
        $("#summary-tax").data('value', tax).text(getFormatedNumber(tax));
        $("#cart-taxes").val(tax);

        calculateOrderSummary();
        return;
      };

      function calculateOrderSummary() {
        var grand = getTotalAmount() + getTax();
        $("#summary-grand-total").data('value', grand).text(getFormatedNumber(grand));
        return;
      }

      function getOrderTotal() {
        return Number($("#summary-total").data('value'));
      };

      function getDiscount() {
        return Number($("#summary-discount").data('value'));
      }

      function getTaxrate() {
        return Number($("#summary-taxrate").text());
      };

      function getTax() {
        return Number($("#summary-tax").data('value'));
      };

      function getShipping() {
        return Number($("#summary-shipping").data('value'));
      };

      function getShippingName() {
        return $("#summary-shipping-name").text().trim();
      };

      function getHandling() {
        return Number($("#summary-handling").data('value'));
      };

      function getPackagingName() {
        return $("#summary-packaging-name").text().trim();
      };

      function getPackaging() {
        return Number($("#summary-packaging").data('value'));
      };

      function getItemQtt(ID) {
        return $("#qtt-" + ID).val();
      };

      function getItemPrice(ID) {
        return $("#price-" + ID).val();
      };

      function getItemTotalWeight(ID) {
        return Number(getItemQtt(ID)) * Number(productObj[ID].shipping_weight);
      }

      function getItemTotal(ID) {
        return Number(getItemQtt(ID)) * Number(getItemPrice(ID));
      };

      function getFormatedValue(value = 0) {
        value = value ? value : 0;
        return parseFloat(value).toFixed(2);
      }

      // Locale-formatted number only (no currency symbol) - use for any visible
      // price/total text. Raw values for math/re-reads live in data-value attrs.
      function getFormatedNumber(value = 0) {
        var decMark = @json(config('system_settings.currency.decimal_mark', '.'));
        var thousandsSep = @json(config('system_settings.currency.thousands_separator', ','));
        value = getFormatedValue(value);
        var parts = value.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSep);
        return parts.join(decMark);
      }

      function getTotalAmount() {
        var total = getOrderTotal();
        if (!total) {
          return total;
        }

        var packaging = getPackaging();
        var handling = getHandling();
        var shipping = getShipping();
        var discount = getDiscount();

        return (total + shipping + handling + packaging) - discount;
      }

      // Remove table rows
      function deleteThisRow(ID) {
        $("tr#" + ID).remove();
        if ($("tbody#items tr").length <= 1) {
          $("#empty-cart").show(); // Show the empty cart message
        }

        calculateOrderTotal();
        return;
      };

      function increaseQttByOne(ID) {
        var qtt = $("#qtt-" + ID).val();
        $("#qtt-" + ID).val(++qtt);
        return true;
      };

      // Save the cart action
      $('body').on('click', '#saveTheCart', function(e) {
        var cart = $("input#cart_id").val();
        var order = <?= isset($order_cart) ? 1 : 'NaN' ?>;

        if (order) {
          var method = '<input name="_method" type="hidden" value="PUT">';
          var url = "{{ url('admin/order/order/') }}/" + cart;
          $("form#form").append(method);
        } else if (cart) {
          var method = '<input name="_method" type="hidden" value="PUT">';
          var url = "{{ url('admin/order/cart/') }}/" + cart;
          $("form#form").append(method);
        } else {
          var url = "{{ url('admin/order/cart') }}";
        }

        $("form#form").attr('action', url);
        $("form#form").submit();
      });

      // Toggle billing address
      $('input#same_as_shipping_address').on('ifChecked', function() {
        $('#billing-address-block').hide()
      });

      $('input#same_as_shipping_address').on('ifUnchecked', function() {
        $('#billing-address-block').show();
      });

      // Address selectors: refresh the preview, and for shipping also the zone, rates and tax
      function loadAddressInfo(addressId, callback) {
        if (!addressId) return;

        $.ajax({
          url: "{{ route('admin.order.order.addressShippingInfo') }}",
          data: {
            address_id: addressId,
            customer_id: "{{ $customer->id }}"
          },
          success: callback
        });
      }

      $('#billing-address-select').on('change', function() {
        loadAddressInfo($(this).val(), function(result) {
          $('#billing-address-preview').html(result.html);
        });
      });

      $('#shipping-address-select').on('change', function() {
        loadAddressInfo($(this).val(), function(result) {
          $('#shipping-address-preview').html(result.html);
          $('#shipping_zone_id').val(result.shipping_zone_id || '');
          $('#summary-zone-name').text(result.shipping_zone_name ? result.shipping_zone_name + ' ' : '');

          // Reset the previously selected shipping rate as it belongs to the old zone
          shipping_options = result.shipping_zone_id ? (result.shipping_options || []) : NaN;
          setShippingCost();
          setTax(result.tax_id);

          $("#global-alert-box").addClass('hidden');
          if (!result.shipping_zone_id) {
            $("#global-alert-msg").html('{{ trans('messages.notice.no_shipping_option_for_the_zone') }}');
            $("#global-alert-box").removeClass('hidden');
            $('#place-order-btn').hide();
          } else {
            if ($.isEmptyObject(shipping_options)) {
              var zoneName = $('<span>').text(result.shipping_zone_name).html();
              $("#global-alert-msg").html('{!! trans('messages.notice.no_rate_for_the_shipping_zone', ['zone' => '__ZONE__']) !!}'.replace('__ZONE__', zoneName));
              $("#global-alert-box").removeClass('hidden');
            }
            $('#place-order-btn').show();
          }
        });
      });
    }(window.jQuery, window, document));
  </script>
@endsection
