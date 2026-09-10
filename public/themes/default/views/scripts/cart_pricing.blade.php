{{--
  Cart / checkout pricing module.
  Formula (matches Cart::calculate_grand_total):

    tax   = subtotal * taxrate / 100
    grand = (subtotal + tax - discount) + shipping + handling + packaging

  Binds qty changes itself (delegated) so pricing still works even if
  other checkout scripts error during init.
--}}
<script type="text/javascript">
  "use strict";

  window.CartPricing = (function($) {
    if (!$ || !$.fn) {
      console.error('[CartPricing] jQuery is required');
      return null;
    }

    function num(value) {
      var n = parseFloat(value);
      return isFinite(n) ? n : 0;
    }

    function read($el) {
      if (!$el || !$el.length) return 0;
      var raw = $el.attr('data-value');
      if (raw === undefined || raw === null || raw === '') {
        raw = $el.data('value');
      }
      return num(raw);
    }

    function write($el, value) {
      if (!$el || !$el.length) return;
      value = num(value);
      $el.attr('data-value', value);
      try { $el.data('value', value); } catch (e) {}
      if (typeof window.getFormatedNumber === 'function') {
        $el.text(window.getFormatedNumber(value));
      } else {
        $el.text(value.toFixed(2));
      }
    }

    function root(cartId) {
      return $('#cartId' + cartId);
    }

    function isDigital(cartId) {
      var $root = root(cartId);
      return String($root.attr('data-cart-type') || '') === 'digital';
    }

    function costs(cartId) {
      var $root = root(cartId);
      var $shipWidget = $('#summary-shipping' + cartId);
      var $packWidget = $('#summary-packaging' + cartId);
      var $discWidget = $('#summary-discount' + cartId);

      var shipping = 0;
      var handling = 0;
      var packaging = 0;
      var discount = 0;

      if ($shipWidget.length) {
        shipping = read($shipWidget);
      } else {
        shipping = num($root.attr('data-shipping'));
        handling = num($root.attr('data-handling'));
      }

      if ($packWidget.length) {
        packaging = read($packWidget);
      } else {
        packaging = num($root.attr('data-packaging'));
      }

      if ($discWidget.length) {
        discount = read($discWidget);
      } else {
        discount = num($root.attr('data-discount'));
      }

      var taxrate = num($('#cart-taxrate' + cartId).val());
      if (!taxrate) {
        taxrate = num($root.attr('data-taxrate'));
      }

      return {
        shipping: shipping,
        handling: handling,
        packaging: packaging,
        discount: discount,
        taxrate: taxrate
      };
    }

    function sumLineTotals(cartId) {
      var total = 0;
      $('.item-total' + cartId).each(function() {
        total += read($(this));
      });
      return total;
    }

    function compute(cartId) {
      var $root = $('#cartId' + cartId);
      var c = costs(cartId);
      var subtotal = sumLineTotals(cartId);
      var taxMode = ($root.attr('data-tax-mode') || 'zone').toLowerCase();
      var tax;
      if (taxMode === 'product') {
        // Product taxes are server-calculated; keep current displayed amount.
        tax = read($('#summary-taxes' + cartId));
        if (!tax) {
          tax = num($root.attr('data-taxes'));
        }
      } else {
        tax = (subtotal * c.taxrate) / 100;
      }
      var grand = (subtotal + tax) - c.discount;

      if (!isDigital(cartId)) {
        grand += c.shipping + c.handling + c.packaging;
      }

      return {
        subtotal: subtotal,
        tax: tax,
        discount: c.discount,
        shipping: c.shipping,
        handling: c.handling,
        packaging: c.packaging,
        grand: grand
      };
    }

    function refreshCombined() {
      var $combined = $('#summary-grand-total-combined');
      if (!$combined.length) return 0;

      var total = 0;
      var subtotal = 0;
      var shipping = 0;
      var taxes = 0;
      var discount = 0;
      var packaging = 0;

      $('[id^="cartId"]').each(function() {
        var $root = $(this);
        var cartId = String($root.attr('data-cart') || '');
        if (!cartId) return;

        var c = compute(cartId);
        total += c.grand;
        subtotal += c.subtotal;
        shipping += c.shipping + c.handling;
        taxes += c.tax;
        discount += c.discount;
        packaging += c.packaging;
      });

      write($combined, total);
      write($('#summary-subtotal-combined'), subtotal);
      write($('#summary-shipping-combined'), shipping);
      write($('#summary-taxes-combined'), taxes);
      write($('#summary-discount-combined'), discount);
      write($('#summary-packaging-combined'), packaging);

      return total;
    }

    function recalculate(cartId) {
      cartId = String(cartId);
      var result = compute(cartId);

      write($('#summary-total' + cartId), result.subtotal);
      write($('#summary-taxes' + cartId), result.tax);
      write($('#summary-grand-total' + cartId), result.grand);
      write($('#summary-store-grand' + cartId), result.grand);

      var $root = root(cartId);
      if ($root.length) {
        $root.attr('data-subtotal', result.subtotal);
        $root.attr('data-grand', result.grand);
      }

      result.combined = refreshCombined();

      if (typeof window.refreshCheckoutPlatformFeePreview === 'function') {
        try {
          window.refreshCheckoutPlatformFeePreview(cartId, result.grand);
        } catch (e) {}
      }

      return result;
    }

    function setItemQuantity(cartId, itemId, qty) {
      cartId = String(cartId);
      itemId = String(itemId);
      qty = Math.max(0, num(qty));

      var $unit = $('#item-price' + cartId + '-' + itemId);
      var $line = $('#item-total' + cartId + '-' + itemId);

      // Digital carts may not have a unit price cell — keep existing line total.
      if ($unit.length) {
        write($line, read($unit) * qty);
      }

      var unitWeight = num($('#unitWeight' + itemId).val());
      $('#itemWeight' + itemId).val(unitWeight * qty);

      return recalculate(cartId);
    }

    var _saveTimers = {};

    function scheduleServerSync(cartId) {
      cartId = String(cartId);
      clearTimeout(_saveTimers[cartId]);
      _saveTimers[cartId] = setTimeout(function() {
        if (typeof window.updateCartOnServerside === 'function') {
          window.updateCartOnServerside(cartId);
        } else if (typeof window.updateCartOnServersideDebounced === 'function') {
          window.updateCartOnServersideDebounced(cartId, 0);
        }
      }, 350);
    }

    function onQtyChanged(input) {
      var $input = $(input);
      var cart = $input.attr('data-cart');
      var item = $input.attr('data-item');
      if (cart === undefined || cart === null || cart === '') {
        cart = $input.data('cart');
      }
      if (item === undefined || item === null || item === '') {
        item = $input.data('item');
      }
      if (cart === undefined || item === undefined) return;

      setItemQuantity(cart, item, $input.val());
      scheduleServerSync(cart);
      $(document).trigger('cart:pricing-updated', [String(cart), String(item), $input.val()]);
    }

    // Delegated — works even when other scripts fail during document.ready.
    $(document).on('change', '.product-info-qty-input', function() {
      onQtyChanged(this);
    });

    return {
      num: num,
      read: read,
      write: write,
      costs: costs,
      compute: compute,
      recalculate: recalculate,
      setItemQuantity: setItemQuantity,
      refreshCombined: refreshCombined,
      onQtyChanged: onQtyChanged,
      scheduleServerSync: scheduleServerSync
    };
  })(window.jQuery);
</script>
