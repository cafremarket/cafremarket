{{-- Guest cart: localStorage until login, then merge into the account cart. --}}
<script>
  (function($) {
    if (typeof $ === 'undefined') {
      return;
    }

    var STORAGE_KEY = 'cafrepay_guest_cart';
    var isGuest = {{ Auth::guard('customer')->check() ? 'false' : 'true' }};
    var previewUrl = @json(route('cart.guestPreview'));
    var mergeUrl = @json(route('cart.mergeGuest'));
    var csrf = $('meta[name="csrf-token"]').attr('content');

    function readCart() {
      try {
        var raw = localStorage.getItem(STORAGE_KEY);
        var items = raw ? JSON.parse(raw) : [];
        return Array.isArray(items) ? items : [];
      } catch (e) {
        return [];
      }
    }

    function writeCart(items) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
      updateBadge(items);
    }

    function cartCount(items) {
      return (items || readCart()).reduce(function(sum, row) {
        return sum + Math.max(1, parseInt(row.quantity, 10) || 1);
      }, 0);
    }

    function updateBadge(items) {
      var count = cartCount(items);
      if (typeof window.increaseCartItem === 'function' || $('#globalCartItemCount').length) {
        var $badge = $('#globalCartItemCount');
        if (count > 0) {
          $badge.removeClass('hidden').text(count);
        } else {
          $badge.addClass('hidden').text('0');
        }
      }
    }

    function slugFromLink(link) {
      if (!link) {
        return null;
      }
      var parts = String(link).split('?')[0].replace(/\/+$/, '').split('/');
      return decodeURIComponent(parts[parts.length - 1] || '');
    }

    function quantityFromButton(btn) {
      var $item = $(btn).closest('.sc-product-item');
      var qtt = $item.find('input.product-info-qty-input').val();
      return Math.max(1, parseInt(qtt, 10) || 1);
    }

    function addItem(slug, quantity) {
      if (!slug) {
        return;
      }
      var items = readCart();
      var found = items.find(function(row) { return row.slug === slug; });
      if (found) {
        found.quantity = Math.max(1, parseInt(found.quantity, 10) || 1) + (quantity || 1);
      } else {
        items.push({ slug: slug, quantity: quantity || 1 });
      }
      writeCart(items);
    }

    function removeItem(slug) {
      writeCart(readCart().filter(function(row) { return row.slug !== slug; }));
    }

    function notify(message, type) {
      type = type || 'success';
      if (window.toastr) {
        toastr[type](message);
        return;
      }
      if (typeof $.notify === 'function') {
        $.notify({ message: message }, { type: type === 'success' ? 'success' : 'warning' });
      }
    }

    function mergeThen(done) {
      var items = readCart();
      if (!items.length) {
        if (typeof done === 'function') {
          done();
        }
        return;
      }

      $.ajax({
        url: mergeUrl,
        method: 'POST',
        data: JSON.stringify({ items: items }),
        contentType: 'application/json',
        processData: false,
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      }).done(function() {
        localStorage.removeItem(STORAGE_KEY);
        if (typeof done === 'function') {
          done();
        }
      }).fail(function() {
        if (typeof done === 'function') {
          done();
        }
      });
    }

    function renderGuestCartPage() {
      var $root = $('#guest-cart-panel');
      var $empty = $('#guest-cart-empty');
      if (!$root.length) {
        return;
      }

      var items = readCart();
      if (!items.length) {
        $root.hide();
        $empty.show();
        return;
      }

      $empty.hide();
      $root.show().html('<p class="text-center my-4">…</p>');

      $.ajax({
        url: previewUrl,
        method: 'POST',
        data: JSON.stringify({ items: items }),
        contentType: 'application/json',
        processData: false,
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      }).done(function(res) {
        var rows = (res && res.data) ? res.data : [];
        if (!rows.length) {
          $root.hide();
          $empty.show();
          return;
        }

        var html = '<div class="sf-checkout__card p-3 mb-3">';
        html += '<h2 class="h4 mb-3">' + @json(trans('theme.shopping_cart')) + '</h2>';
        html += '<div class="table-responsive"><table class="table"><tbody>';
        rows.forEach(function(row) {
          html += '<tr data-slug="' + $('<div>').text(row.slug).html() + '">';
          html += '<td width="90"><img src="' + row.image + '" alt="" width="72"></td>';
          html += '<td><a href="' + row.url + '">' + $('<div>').text(row.title || row.slug).html() + '</a>';
          if (row.shop) {
            html += '<div class="text-muted small">' + $('<div>').text(row.shop).html() + '</div>';
          }
          html += '</td>';
          html += '<td>' + $('<div>').text(String(row.quantity)).html() + '</td>';
          html += '<td>' + (row.line_total_formatted || row.price_formatted || '') + '</td>';
          html += '<td><a href="javascript:void(0);" class="js-guest-cart-remove" data-slug="' + $('<div>').text(row.slug).html() + '">&times;</a></td>';
          html += '</tr>';
        });
        html += '</tbody></table></div>';
        html += '<p class="text-muted small mb-3">' + @json(trans('theme.guest_cart_login_help')) + '</p>';
        html += '<button type="button" class="btn btn-primary btn-block js-open-login">' + @json(trans('theme.login_to_checkout')) + '</button>';
        html += '</div>';
        $root.html(html);
      }).fail(function() {
        $root.hide();
        $empty.show();
      });
    }

    window.CafrepayGuestCart = {
      isGuest: isGuest,
      read: readCart,
      add: addItem,
      mergeThen: mergeThen
    };

    if (isGuest) {
      updateBadge();

      document.addEventListener('click', function(e) {
        var buyNow = e.target.closest('#buy-now-btn');
        var addBtn = e.target.closest('.sc-add-to-cart');
        if (!addBtn && !buyNow) {
          return;
        }

        var btn = addBtn || buyNow;
        if (btn.hasAttribute('disabled') || btn.classList.contains('disabled')) {
          return;
        }

        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') {
          e.stopImmediatePropagation();
        }
        var slug = slugFromLink(btn.getAttribute('data-link') || btn.getAttribute('href'));
        addItem(slug, quantityFromButton(btn));
        updateBadge();

        if (window.toastr) {
          toastr.success(@json(trans('theme.notify.item_added_to_cart')));
        }

        if (buyNow && typeof window.openCustomerLoginModal === 'function') {
          window.openCustomerLoginModal();
        }
      }, true);

      $(document).on('click', '.js-guest-cart-remove', function() {
        removeItem($(this).data('slug'));
        renderGuestCartPage();
      });

      $(function() {
        renderGuestCartPage();
      });
    } else if (readCart().length) {
      mergeThen(function() {
        if (!readCart().length) {
          window.location.reload();
        }
      });
    }
  })(window.jQuery);
</script>
