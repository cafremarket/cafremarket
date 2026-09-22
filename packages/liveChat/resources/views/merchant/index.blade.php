@extends('merchant.layouts.app')

@section('page_title', trans('nav.chats') ?? 'Chat')

@section('content')
  <div class="mpc" id="chatbox" data-ws-room="{{ get_vendor_chat_room_id() }}"
       data-ws-url=""
       data-csrf="{{ csrf_token() }}">
    <div class="mpc__shell">
      <aside class="mpc__inbox" id="mpc-inbox">
        <div class="mpc__inbox-head">
          <div>
            <h2>{{ trans('nav.chats') ?? 'Messages' }}</h2>
            <p>{{ $chats->count() }} {{ \Illuminate\Support\Str::plural('conversation', $chats->count()) }}</p>
          </div>
        </div>

        <div class="mpc__search">
          <i class="fa fa-search"></i>
          <input type="search" id="mpc-search" placeholder="Search customers…" autocomplete="off">
        </div>

        <div class="mpc__list" id="leftsidebar">
          <div class="sidebarContent">
            @forelse($chats as $conversation)
              @php
                $lastMessage = (string) $conversation->last_message();
                $productPrefix = '[product_share]';
                $orderPrefix = '[order_share]';
                if (str_starts_with($lastMessage, $productPrefix)) {
                    $shared = json_decode(substr($lastMessage, strlen($productPrefix)), true);
                    $preview = '[Product] '.($shared['title'] ?? 'Shared item');
                } elseif (str_starts_with($lastMessage, $orderPrefix)) {
                    $shared = json_decode(substr($lastMessage, strlen($orderPrefix)), true);
                    $preview = '[Order] '.($shared['order_number'] ?? $shared['title'] ?? 'Shared order');
                } else {
                    $preview = strip_tags($lastMessage);
                }
              @endphp
              <button type="button"
                      class="mpc-row sidebarBody {{ $conversation->isUnread() ? 'is-unread' : '' }}"
                      id="chat-{{ $conversation->id }}"
                      data-conversation-id="{{ $conversation->id }}"
                      data-customer-id="{{ $conversation->customer_id }}"
                      data-link="{{ route('merchant.support.chat_conversation.show', $conversation, false) }}"
                      data-name="{{ $conversation->customer->getName() }}">
                <img src="{{ get_avatar_src($conversation->customer, 'mini') }}" alt="">
                <span class="mpc-row__body">
                  <span class="mpc-row__top">
                    <span class="name-meta">{{ $conversation->customer->getName() }}</span>
                    <span class="time-meta">{{ $conversation->updated_at->diffForHumans() }}</span>
                  </span>
                  <span class="mpc-row__bottom">
                    <span class="excerpt">{{ \Illuminate\Support\Str::limit($preview, 72) }}</span>
                    @if ($conversation->isUnread())
                      <span class="mpc-row__dot" aria-hidden="true"></span>
                    @endif
                  </span>
                </span>
              </button>
            @empty
              <div class="mpc__empty-list">
                <i class="fa fa-inbox"></i>
                <p>No customer chats yet</p>
              </div>
            @endforelse
          </div>
        </div>
      </aside>

      <section class="mpc__thread" id="chatConversation">
        <div class="mpc__placeholder">
          <div class="mpc__placeholder-icon"><i class="fa fa-comments"></i></div>
          <h3>Select a conversation</h3>
          <p>Pick a customer on the left to reply in real time.</p>
        </div>
      </section>
    </div>
  </div>
@endsection

@section('scripts')
<script>
(function () {
  'use strict';

  var root = document.getElementById('chatbox');
  if (!root) return;

  var csrf = root.getAttribute('data-csrf') || (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var room = root.getAttribute('data-ws-room') || '';
  var sending = false;
  var socket = null;

  function qs(sel, el) { return (el || document).querySelector(sel); }
  function qsa(sel, el) { return Array.prototype.slice.call((el || document).querySelectorAll(sel)); }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  // Escapes first, then wraps bare URLs in a real link — safe against XSS
  // since the regex only ever runs against already-escaped text.
  function linkify(s) {
    return esc(s).replace(/(https?:\/\/[^\s<]+)/g, function (url) {
      return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
    });
  }

  function clock(iso) {
    try {
      var d = iso ? new Date(iso) : new Date();
      if (isNaN(d.getTime())) return '';
      var h = d.getHours(), m = d.getMinutes(), ap = h >= 12 ? 'PM' : 'AM';
      h = h % 12; if (!h) h = 12;
      return h + ':' + (m < 10 ? '0' : '') + m + ' ' + ap;
    } catch (e) { return ''; }
  }

  function dayKey(iso) {
    try {
      var d = iso ? new Date(iso) : new Date();
      if (isNaN(d.getTime())) return '';
      return d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
    } catch (e) { return ''; }
  }

  function scrollBox() {
    var box = qs('#conversationBox');
    if (box) box.scrollTop = box.scrollHeight;
  }

  function setThreadOpen(open) {
    root.classList.toggle('mpc--thread-open', !!open);
  }

  function showError(msg) {
    var el = qs('#mpc-send-error');
    if (!el) return;
    el.hidden = !msg;
    el.textContent = msg || '';
  }

  function parseShare(text) {
    if (!text || typeof text !== 'string') return null;
    var kinds = [
      { prefix: '[order_share]', type: 'order' },
      { prefix: '[product_share]', type: 'product' }
    ];
    for (var i = 0; i < kinds.length; i++) {
      if (text.indexOf(kinds[i].prefix) !== 0) continue;
      try {
        var data = JSON.parse(text.slice(kinds[i].prefix.length));
        if (data && typeof data === 'object') {
          data._shareType = kinds[i].type;
          return data;
        }
      } catch (e) {}
      return null;
    }
    return null;
  }

  function previewText(text, attachments) {
    var share = parseShare(text);
    if (share) {
      if (share._shareType === 'order') {
        return '[Order] ' + (share.order_number || share.title || 'Shared order');
      }
      return '[Product] ' + (share.title || 'Shared item');
    }
    if (attachments && attachments.length) {
      var plain = String(text || '').replace(/<[^>]*>/g, '').trim().toLowerCase();
      if (!plain || plain === '[attachment]') return '[Attachment]';
    }
    return String(text || '').slice(0, 72);
  }

  function quoteHtml(quoted) {
    if (!quoted || !quoted.id) return '';
    return '<button type="button" class="chat-quote" data-parent-id="' + esc(quoted.id) + '">' +
      '<strong>' + esc(quoted.sender_name || '') + '</strong>' +
      '<span>' + esc(quoted.reply || '') + '</span></button>';
  }

  function currentQuote() {
    var idEl = qs('#mpc-parent-id');
    var id = idEl && idEl.value ? parseInt(idEl.value, 10) : 0;
    if (!id) return null;
    return {
      id: id,
      sender_name: (qs('#mpc-quote-name') || {}).textContent || '',
      reply: (qs('#mpc-quote-text') || {}).textContent || ''
    };
  }

  function setQuoteFromBubble(bubble) {
    if (!bubble) return;
    var id = bubble.getAttribute('data-reply-id');
    if (!id) return;
    var idEl = qs('#mpc-parent-id');
    var preview = qs('#mpc-quote-preview');
    var nameEl = qs('#mpc-quote-name');
    var textEl = qs('#mpc-quote-text');
    if (idEl) idEl.value = id;
    if (nameEl) nameEl.textContent = bubble.getAttribute('data-quote-name') || 'Reply';
    if (textEl) textEl.textContent = bubble.getAttribute('data-quote-text') || '';
    if (preview) preview.hidden = false;
    var ta = qs('#chat-form textarea[name="message"]');
    if (ta) ta.focus();
  }

  function clearQuote() {
    var idEl = qs('#mpc-parent-id');
    var preview = qs('#mpc-quote-preview');
    if (idEl) idEl.value = '';
    if (preview) preview.hidden = true;
  }

  function bindQuoteUi() {
    var box = qs('#conversationBox');
    if (box && !box._quoteBound) {
      box._quoteBound = true;
      var timer = null;
      box.addEventListener('pointerdown', function (e) {
        var bubble = e.target.closest('.mpc-bubble');
        if (!bubble || !bubble.getAttribute('data-reply-id')) return;
        if (e.target.closest('a,button,input,textarea')) return;
        timer = setTimeout(function () { setQuoteFromBubble(bubble); }, 450);
      });
      ['pointerup', 'pointerleave', 'pointercancel'].forEach(function (ev) {
        box.addEventListener(ev, function () { clearTimeout(timer); });
      });
      box.addEventListener('click', function (e) {
        var q = e.target.closest('.chat-quote');
        if (!q) return;
        e.preventDefault();
        var id = q.getAttribute('data-parent-id');
        var target = qs('#conversationBox [data-reply-id="' + id + '"]');
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    }
    var clearBtn = qs('#mpc-quote-clear');
    if (clearBtn && !clearBtn._bound) {
      clearBtn._bound = true;
      clearBtn.addEventListener('click', clearQuote);
    }
  }

  function bubbleHtml(text, outgoing, meta) {
    meta = meta || {};
    var share = parseShare(text);
    var body = '';
    if (meta.type === 'location' && meta.payload) {
      body = '<div class="mpc-share mpc-share--location"><div class="mpc-share__icon"><i class="fa fa-map-marker"></i></div><div>' +
        '<div class="mpc-share__title">' + esc(meta.payload.label || 'Location') + '</div>' +
        '<a href="https://www.google.com/maps/search/?api=1&query=' + esc(meta.payload.lat) + ',' + esc(meta.payload.lng) + '" target="_blank" rel="noopener">Open in Maps</a></div></div>';
    } else if (meta.type === 'contact' && meta.payload) {
      body = '<div class="mpc-share mpc-share--contact"><div class="mpc-share__icon"><i class="fa fa-user"></i></div><div>' +
        '<div class="mpc-share__title">' + esc(meta.payload.name || 'Contact') + '</div>' +
        (meta.payload.phone ? ('<div class="mpc-share__price">' + esc(meta.payload.phone) + '</div><a href="tel:' + esc(meta.payload.phone) + '">Call</a>') : '') +
        '</div></div>';
    } else if (share && share._shareType === 'order') {
      var line = [share.total, share.status].filter(Boolean).join(' · ');
      // The stored share.url is always the customer-facing storefront page —
      // a vendor viewing their own chat panel needs their own order-management
      // page instead (mirrors routes/admin/Order.php's "details" route, which
      // Merchant.php nests under merchant/order/{order}/details).
      var vendorOrderUrl = share.order_id ? ('/merchant/order/' + encodeURIComponent(share.order_id) + '/details') : (share.url || '#');
      body = '<div class="mpc-share mpc-share--order">' +
        (share.image ? '<img src="' + esc(share.image) + '" alt="">' : '') +
        '<div><div class="mpc-share__title">' + esc(share.title || ('Order #' + (share.order_number || ''))) + '</div>' +
        (line ? '<div class="mpc-share__price">' + esc(line) + '</div>' : '') +
        '<a href="' + esc(vendorOrderUrl) + '" target="_blank" rel="noopener">View order</a></div></div>';
    } else if (share) {
      body = '<div class="mpc-share"><img src="' + esc(share.image || '') + '" alt=""><div>' +
        '<div class="mpc-share__title">' + esc(share.title || '') + '</div>' +
        '<div class="mpc-share__price">' + esc(share.price || '') + '</div>' +
        '<a href="' + esc(share.url || '#') + '" target="_blank" rel="noopener">View</a></div></div>';
    } else {
      body = '<p class="mpc-bubble__text">' + linkify(text || '') + '</p>';
    }
    if (meta.quoted) body = quoteHtml(meta.quoted) + body;
    if (meta.attachments && meta.attachments.length) {
      body += '<div class="mpc-atts">';
      meta.attachments.forEach(function (a) {
        var url = a.url || '';
        var ext = String(a.extension || '').toLowerCase();
        var isImg = ['jpg','jpeg','png','gif','webp'].indexOf(ext) !== -1;
        if (isImg) {
          body += '<a href="' + esc(url) + '" target="_blank" rel="noopener"><img src="' + esc(url) + '" alt=""></a>';
        } else {
          body += '<a href="' + esc(url) + '" target="_blank" rel="noopener" class="mpc-atts__file"><i class="fa fa-paperclip"></i> ' + esc(a.name || 'File') + '</a>';
        }
      });
      body += '</div>';
    }
    var cls = outgoing ? 'mpc-bubble mpc-bubble--out' : 'mpc-bubble mpc-bubble--in';
    var attrs = '';
    if (meta.replyId) attrs += ' data-reply-id="' + esc(meta.replyId) + '"';
    if (meta.pending) attrs += ' data-pending="1"';
    if (meta.createdAt) attrs += ' data-created-at="' + esc(meta.createdAt) + '"';
    var qName = meta.quoteName || (outgoing ? 'You' : '');
    var qText = meta.quoteText || String(text || '').slice(0, 80);
    if (meta.replyId) {
      attrs += ' data-quote-name="' + esc(qName) + '"';
      attrs += ' data-quote-text="' + esc(qText) + '"';
      attrs += ' data-sender-type="' + esc(outgoing ? 'merchant' : 'customer') + '"';
    }
    return '<div class="' + cls + '"' + attrs + '><div class="mpc-bubble__body">' + body +
      '<time>' + esc(meta.time || clock(meta.createdAt)) + '</time></div></div>';
  }

  function ensureDay(iso) {
    var box = qs('#conversationBox');
    if (!box) return;
    var key = dayKey(iso);
    if (!key) return;
    var days = box.querySelectorAll('.mpc-day');
    var last = days.length ? days[days.length - 1] : null;
    if (last && last.getAttribute('data-day') === key) return;
    var sep = document.createElement('div');
    sep.className = 'mpc-day';
    sep.setAttribute('data-day', key);
    sep.innerHTML = '<span>Today</span>';
    box.appendChild(sep);
  }

  function findRow(conversationId, customerId) {
    var id = conversationId != null && conversationId !== '' ? String(conversationId) : '';
    var cid = customerId != null && customerId !== '' ? String(customerId) : '';
    if (id) {
      var byId = qs('#chat-' + id) || qs('.mpc-row[data-conversation-id="' + id + '"]');
      if (byId) return byId;
    }
    if (cid) {
      return qs('#chat-' + cid) || qs('.mpc-row[data-customer-id="' + cid + '"]');
    }
    return null;
  }

  function updateRowPreview(conversationId, customerId, text, timeLabel, attachments) {
    var row = findRow(conversationId, customerId);
    if (!row) return;
    var ex = row.querySelector('.excerpt');
    var tm = row.querySelector('.time-meta');
    if (ex) ex.textContent = previewText(text, attachments).slice(0, 72);
    if (tm) tm.textContent = timeLabel || 'just now';
  }

  function openMatches(result) {
    var open = qs('.mpc-thread__head');
    if (!open) return false;
    var openConv = open.getAttribute('data-conversation-id') || String(open.id || '').replace('openChatbox-', '');
    var openCust = open.getAttribute('data-customer-id') || '';
    var rid = result.conversation_id != null ? String(result.conversation_id) : '';
    var cid = result.customer_id != null ? String(result.customer_id) : '';
    if (rid && openConv && rid === openConv) return true;
    if (cid && openCust && cid === openCust) return true;
    return false;
  }

  function loadConversation(link, row) {
    if (!link) return;
    var pane = qs('#chatConversation');
    pane.innerHTML = '<div class="mpc__placeholder"><div class="mpc__placeholder-icon"><i class="fa fa-circle-o-notch fa-spin"></i></div><p>Loading…</p></div>';
    setThreadOpen(true);
    qsa('.mpc-row').forEach(function (r) { r.classList.remove('is-active'); });
    if (row) {
      row.classList.add('is-active');
      row.classList.remove('is-unread');
      var dot = row.querySelector('.mpc-row__dot');
      if (dot) dot.remove();
    }
    fetch(link, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html', 'X-CSRF-TOKEN': csrf }
    }).then(function (res) {
      if (!res.ok) throw new Error('Failed to load conversation (' + res.status + ')');
      return res.text();
    }).then(function (html) {
      pane.innerHTML = html;
      scrollBox();
      bindComposer();
      bindQuoteUi();
    }).catch(function (err) {
      pane.innerHTML = '<div class="mpc__placeholder"><h3>Could not open chat</h3><p>' + esc(err.message || 'Error') + '</p></div>';
    });
  }

  function bindComposer() {
    var form = qs('#chat-form');
    if (!form || form._mpcBound) return;
    form._mpcBound = true;

    var fileInput = qs('#merchantChatFile', form);
    var preview = qs('#mpc-attach-preview');
    var previewName = qs('#mpc-attach-name');
    var clearBtn = qs('#mpc-attach-clear');
    var backBtn = qs('#mpc-back-list');

    if (backBtn) backBtn.addEventListener('click', function () { setThreadOpen(false); });
    if (fileInput) {
      fileInput.addEventListener('change', function () {
        if (!fileInput.files || !fileInput.files.length) {
          if (preview) preview.hidden = true;
          return;
        }
        if (previewName) previewName.textContent = fileInput.files[0].name;
        if (preview) preview.hidden = false;
      });
    }
    if (clearBtn && fileInput) {
      clearBtn.addEventListener('click', function () {
        fileInput.value = '';
        if (preview) preview.hidden = true;
      });
    }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      sendReply(form);
    });
    var ta = qs('textarea[name="message"]', form);
    if (ta) {
      ta.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          sendReply(form);
        }
      });
      ta.focus();
    }
    bindAttachMenu();
    bindQuoteUi();
  }

  function bindAttachMenu() {
    var toggle = qs('#mpc-attach-toggle');
    var menu = qs('#mpc-attach-menu');
    if (!toggle || !menu || toggle._mpcBound) return;
    toggle._mpcBound = true;

    function closeMenu() {
      menu.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = menu.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (!menu.classList.contains('is-open')) return;
      if (e.target.closest('#mpc-attach-menu') || e.target.closest('#mpc-attach-toggle')) return;
      closeMenu();
    });

    var mediaBtn = qs('#mpc-menu-media');
    var fileInput = qs('#merchantChatFile');
    if (mediaBtn && fileInput) {
      mediaBtn.addEventListener('click', function () { closeMenu(); fileInput.click(); });
    }

    var shareProductBtn = qs('#mpc-menu-share-product');
    if (shareProductBtn) {
      shareProductBtn.addEventListener('click', function () { closeMenu(); openPickerModal('product'); });
    }

    var shareOrderBtn = qs('#mpc-menu-share-order');
    if (shareOrderBtn) {
      shareOrderBtn.addEventListener('click', function () { closeMenu(); openPickerModal('order'); });
    }

    bindPickerModal();

    var orderBtn = qs('#mpc-menu-order');
    var orderModal = qs('#mpc-order-modal');
    if (orderBtn && orderModal) {
      orderBtn.addEventListener('click', function () { closeMenu(); openOrderModal(); });
      var orderCancel = qs('#mpc-order-cancel');
      if (orderCancel) orderCancel.addEventListener('click', function () { orderModal.hidden = true; });
      var addItem = qs('#mpc-order-add-item');
      if (addItem && !addItem._mpcBound) {
        addItem._mpcBound = true;
        addItem.addEventListener('click', function () { addOrderItemRow(); });
      }
      var submit = qs('#mpc-order-submit');
      if (submit && !submit._mpcBound) {
        submit._mpcBound = true;
        submit.addEventListener('click', submitCustomOrder);
      }
      bindOrderTotalsInputs();
    }

    var orderShortcut = qs('#mpc-order-shortcut');
    if (orderShortcut && !orderShortcut._mpcBound) {
      orderShortcut._mpcBound = true;
      orderShortcut.addEventListener('click', function () { openOrderModal(); });
    }
  }

  var orderSearchDebounce = null;

  function orderRowSubtotal(row) {
    var qty = parseFloat((row.querySelector('.mpc-oi-qty') || {}).value) || 0;
    var price = parseFloat((row.querySelector('.mpc-oi-price') || {}).value) || 0;
    return qty * price;
  }

  function collectOrderItems() {
    var items = [];
    qsa('.mpc-oi-row').forEach(function (row) {
      var title = (row.querySelector('.mpc-oi-title') || {}).value || '';
      var qty = parseInt((row.querySelector('.mpc-oi-qty') || {}).value, 10) || 0;
      var price = parseFloat((row.querySelector('.mpc-oi-price') || {}).value);
      var inventoryId = row.getAttribute('data-inventory-id');
      title = title.trim();
      if (!title || !qty || isNaN(price) || price < 0) return;
      var item = { title: title, quantity: qty, unit_price: price };
      if (inventoryId) item.inventory_id = parseInt(inventoryId, 10);
      items.push(item);
    });
    return items;
  }

  var productTotalsDebounce = null;

  function popoverContentFromBreakdown(lines, withTaxName) {
    return lines.map(function (line) {
      var label = withTaxName ? (line.title + ' — ' + line.tax_name) : line.title;
      return esc(label) + ' — ' + esc(line.amount);
    }).join('<br>');
  }

  function refreshPopover(id, content) {
    var el = qs(id);
    if (!el) return;
    el.setAttribute('data-content', content);
    if (window.jQuery) {
      var $el = window.jQuery(el);
      // Explicit container: 'body' — these popovers live inside .mpc-modal,
      // which is overflow-y:auto, and Bootstrap's default container (the
      // trigger's parent) would get clipped by that scroll box otherwise.
      if ($el.data('bs.popover')) { $el.popover('destroy'); }
      $el.popover({ container: 'body', html: true, trigger: 'hover focus click', placement: 'left' });
    }
  }

  // Once the seller types into Tax/Shipping themselves, that value is now a
  // deliberate custom override — the product-based recompute must never
  // clobber it again (until the modal is reopened fresh). Setting .value
  // from JS never fires 'input'/'change', so these flags only ever flip on
  // genuine user typing, not on our own auto-fill below.
  var taxManuallyEdited = false;
  var shippingManuallyEdited = false;

  function refreshProductBasedTotals() {
    var modal = qs('#mpc-order-modal');
    var url = modal ? modal.getAttribute('data-calculate-totals-url') : '';
    var items = collectOrderItems().filter(function (it) { return !!it.inventory_id; });
    var taxInfo = qs('#mpc-tax-info');
    var shippingInfo = qs('#mpc-shipping-info');

    if (!url || !items.length) {
      if (taxInfo) taxInfo.hidden = true;
      if (shippingInfo) shippingInfo.hidden = true;
      return;
    }

    fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify({ items: items })
    }).then(function (res) { return res.json(); }).then(function (data) {
      var taxField = qs('#mpc-order-tax');
      var taxTypeField = qs('#mpc-order-tax-type');
      if (data.tax_amount > 0 && taxField && !taxManuallyEdited) {
        taxField.value = data.tax_amount;
        if (taxTypeField) taxTypeField.value = 'amount';
        if (taxInfo) { taxInfo.hidden = false; refreshPopover('#mpc-tax-info', popoverContentFromBreakdown(data.tax_breakdown || [], true)); }
      } else if (taxInfo) {
        // Manually edited (or nothing product-based found) — this is now a
        // custom value, not a product-derived one, so no "based on" info icon.
        taxInfo.hidden = true;
      }

      var shippingField = qs('#mpc-order-shipping');
      if (data.shipping_amount > 0 && shippingField && !shippingManuallyEdited) {
        shippingField.value = data.shipping_amount;
        if (shippingInfo) { shippingInfo.hidden = false; refreshPopover('#mpc-shipping-info', popoverContentFromBreakdown(data.shipping_breakdown || [], false)); }
      } else if (shippingInfo) {
        shippingInfo.hidden = true;
      }

      recalcOrderTotals();
    }).catch(function () {
      if (taxInfo) taxInfo.hidden = true;
      if (shippingInfo) shippingInfo.hidden = true;
    });
  }

  function scheduleProductBasedTotals() {
    if (productTotalsDebounce) clearTimeout(productTotalsDebounce);
    productTotalsDebounce = setTimeout(refreshProductBasedTotals, 400);
  }

  function recalcOrderTotals() {
    var subtotal = 0;
    qsa('.mpc-oi-row').forEach(function (row) {
      var lineTotal = orderRowSubtotal(row);
      subtotal += lineTotal;
      var lineTotalEl = row.querySelector('.mpc-oi-line-total');
      if (lineTotalEl) lineTotalEl.textContent = lineTotal.toFixed(2);
    });

    var shipping = parseFloat((qs('#mpc-order-shipping') || {}).value) || 0;
    var taxRaw = parseFloat((qs('#mpc-order-tax') || {}).value) || 0;
    var discountRaw = parseFloat((qs('#mpc-order-discount') || {}).value) || 0;
    var taxType = (qs('#mpc-order-tax-type') || {}).value || 'amount';
    var discountType = (qs('#mpc-order-discount-type') || {}).value || 'amount';

    var tax = taxType === 'percent' ? subtotal * (taxRaw / 100) : taxRaw;
    var discount = discountType === 'percent' ? subtotal * (discountRaw / 100) : discountRaw;
    var grandTotal = Math.max(0, subtotal + shipping + tax - discount);

    var subtotalEl = qs('#mpc-order-subtotal');
    var grandTotalEl = qs('#mpc-order-grand-total');
    if (subtotalEl) subtotalEl.textContent = subtotal.toFixed(2);
    if (grandTotalEl) grandTotalEl.textContent = grandTotal.toFixed(2);

    return { subtotal: subtotal, tax: tax, discount: discount };
  }

  function closeOrderSearchResults(row) {
    var results = row.querySelector('.mpc-oi-results');
    if (results) { results.innerHTML = ''; results.hidden = true; }
  }

  function selectOrderProduct(row, product) {
    var titleInput = row.querySelector('.mpc-oi-title');
    var priceInput = row.querySelector('.mpc-oi-price');
    var thumb = row.querySelector('.mpc-oi-thumb');
    if (titleInput) titleInput.value = product.title;
    if (priceInput) priceInput.value = product.raw_price;
    row.setAttribute('data-inventory-id', product.inventory_id);
    if (thumb) {
      if (product.image) { thumb.src = product.image; thumb.classList.add('mpc-oi-thumb--visible'); }
      else { thumb.classList.remove('mpc-oi-thumb--visible'); }
    }
    closeOrderSearchResults(row);
    recalcOrderTotals();
    scheduleProductBasedTotals();
  }

  function searchOrderProducts(row, term) {
    var modal = qs('#mpc-order-modal');
    var url = modal ? modal.getAttribute('data-inventory-search-url') : '';
    var results = row.querySelector('.mpc-oi-results');
    if (!url || !results) return;
    if (!term) { closeOrderSearchResults(row); return; }

    fetch(url + '?q=' + encodeURIComponent(term), {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    }).then(function (res) { return res.json(); }).then(function (json) {
      var items = (json && json.data) || [];
      results.innerHTML = '';
      if (!items.length) {
        results.innerHTML = '<div class="mpc-order-search-empty">No catalog match — will be added as a custom item.</div>';
        results.hidden = false;
        return;
      }
      items.forEach(function (product) {
        var opt = document.createElement('button');
        opt.type = 'button';
        opt.className = 'mpc-order-search-option';
        opt.innerHTML = (product.image ? '<img src="' + product.image + '" alt="">' : '<span class="mpc-order-search-noimg"></span>') +
          '<span class="mpc-order-search-info"><strong></strong><small></small></span>';
        opt.querySelector('strong').textContent = product.title;
        opt.querySelector('small').textContent = product.price + ' · Stock: ' + product.stock;
        opt.addEventListener('click', function () { selectOrderProduct(row, product); });
        results.appendChild(opt);
      });
      results.hidden = false;
    }).catch(function () { closeOrderSearchResults(row); });
  }

  function addOrderItemRow() {
    var wrap = qs('#mpc-order-items');
    if (!wrap) return;
    var row = document.createElement('tr');
    row.className = 'mpc-oi-row';
    row.innerHTML =
      '<td class="mpc-oi-product-cell">' +
        '<img class="mpc-oi-thumb" alt="">' +
        '<div class="mpc-oi-search">' +
          '<input type="text" class="mpc-oi-title" placeholder="Search your catalog or type a custom item">' +
          '<div class="mpc-oi-results" hidden></div>' +
        '</div>' +
      '</td>' +
      '<td><input type="number" class="mpc-oi-qty" min="1" value="1"></td>' +
      '<td><input type="number" class="mpc-oi-price" min="0" step="0.01" placeholder="0.00"></td>' +
      '<td class="mpc-oi-line-total">0.00</td>' +
      '<td><button type="button" class="mpc-oi-remove" aria-label="Remove item">&times;</button></td>';

    var titleInput = row.querySelector('.mpc-oi-title');
    titleInput.addEventListener('input', function () {
      row.removeAttribute('data-inventory-id'); // typing again means "not the picked product" until re-selected
      var term = titleInput.value.trim();
      if (orderSearchDebounce) clearTimeout(orderSearchDebounce);
      orderSearchDebounce = setTimeout(function () { searchOrderProducts(row, term); }, 250);
    });
    titleInput.addEventListener('blur', function () {
      setTimeout(function () { closeOrderSearchResults(row); }, 150); // allow click on a result first
    });

    row.querySelector('.mpc-oi-qty').addEventListener('input', function () { recalcOrderTotals(); scheduleProductBasedTotals(); });
    row.querySelector('.mpc-oi-price').addEventListener('input', function () { recalcOrderTotals(); scheduleProductBasedTotals(); });
    row.querySelector('.mpc-oi-remove').addEventListener('click', function () {
      if (wrap.children.length > 1) { row.parentNode.removeChild(row); recalcOrderTotals(); scheduleProductBasedTotals(); }
    });
    wrap.appendChild(row);
  }

  function openOrderModal() {
    var wrap = qs('#mpc-order-items');
    var modal = qs('#mpc-order-modal');
    var err = qs('#mpc-order-error');
    if (err) { err.hidden = true; err.textContent = ''; }
    if (wrap) wrap.innerHTML = '';
    addOrderItemRow();
    taxManuallyEdited = false;
    shippingManuallyEdited = false;

    // Reset to the shop's own configured defaults (server-rendered), not to
    // a blind zero — the seller can still edit any of these before sharing.
    var defaultShipping = modal ? (modal.getAttribute('data-default-shipping') || '0') : '0';
    var defaultTax = modal ? (modal.getAttribute('data-default-tax') || '0') : '0';
    var defaultTaxType = modal ? (modal.getAttribute('data-default-tax-type') || 'amount') : 'amount';

    var shipping = qs('#mpc-order-shipping'), tax = qs('#mpc-order-tax'), discount = qs('#mpc-order-discount'), note = qs('#mpc-order-note');
    if (shipping) shipping.value = defaultShipping;
    if (tax) tax.value = defaultTax;
    if (discount) discount.value = '0';
    if (note) note.value = '';
    var taxType = qs('#mpc-order-tax-type'), discountType = qs('#mpc-order-discount-type');
    if (taxType) taxType.value = defaultTaxType;
    if (discountType) discountType.value = 'amount';
    var taxInfo = qs('#mpc-tax-info'), shippingInfo = qs('#mpc-shipping-info');
    if (taxInfo) taxInfo.hidden = true;
    if (shippingInfo) shippingInfo.hidden = true;
    resetOrderAddresses();
    recalcOrderTotals();
    if (modal) modal.hidden = false;
  }

  // Pre-select the customer's default shipping/billing address and wire the
  // "billing same as shipping" toggle.
  function resetOrderAddresses() {
    var section = qs('.mpc-addr-section');
    if (!section) return;

    var defShip = section.getAttribute('data-default-shipping');
    var defBill = section.getAttribute('data-default-billing') || defShip;
    var check = function (name, value) {
      var input = qs('input[name="' + name + '"][value="' + value + '"]') || qs('input[name="' + name + '"]');
      if (input) input.checked = true;
    };
    check('mpc_ship_addr', defShip);
    check('mpc_bill_addr', defBill);

    var same = qs('#mpc-order-same-billing');
    var billWrap = qs('#mpc-bill-addr-wrap');
    if (same) {
      same.checked = !defBill || defBill === defShip;
      if (billWrap) billWrap.hidden = same.checked;
      if (!same._mpcBound) {
        same._mpcBound = true;
        same.addEventListener('change', function () {
          var wrap = qs('#mpc-bill-addr-wrap');
          if (wrap) wrap.hidden = same.checked;
        });
      }
    }
  }

  function bindOrderTotalsInputs() {
    var shippingSelectors = ['#mpc-order-shipping'];
    var taxSelectors = ['#mpc-order-tax', '#mpc-order-tax-type'];
    ['#mpc-order-shipping', '#mpc-order-tax', '#mpc-order-discount', '#mpc-order-tax-type', '#mpc-order-discount-type'].forEach(function (sel) {
      var el = qs(sel);
      if (el && !el._mpcTotalsBound) {
        el._mpcTotalsBound = true;
        var markManual = function () {
          if (shippingSelectors.indexOf(sel) !== -1) shippingManuallyEdited = true;
          if (taxSelectors.indexOf(sel) !== -1) taxManuallyEdited = true;
        };
        el.addEventListener('input', function () { markManual(); recalcOrderTotals(); });
        el.addEventListener('change', function () { markManual(); recalcOrderTotals(); });
      }
    });
  }

  function submitCustomOrder() {
    var url = (qs('.mpc-composer') || {}).getAttribute('data-custom-order-url');
    var err = qs('#mpc-order-error');
    if (!url) { if (err) { err.hidden = false; err.textContent = 'Missing order URL. Reload the page.'; } return; }

    var items = collectOrderItems();
    if (!items.length) {
      if (err) { err.hidden = false; err.textContent = 'Add at least one valid item.'; }
      return;
    }

    var totals = recalcOrderTotals();

    var body = {
      items: items,
      shipping_cost: parseFloat((qs('#mpc-order-shipping') || {}).value) || 0,
      tax: totals.tax,
      discount: totals.discount,
      // No payment_method_id — the customer picks their own and pays once they open the order.
      billing_address: ((qs('#mpc-order-billing') || {}).value || '').trim(),
      note: ((qs('#mpc-order-note') || {}).value || '').trim()
    };

    // Seller-selected customer addresses (address selector)
    if (qs('.mpc-addr-section')) {
      var shipAddr = qs('input[name="mpc_ship_addr"]:checked');
      var sameBilling = qs('#mpc-order-same-billing');
      var billAddr = (sameBilling && sameBilling.checked) ? shipAddr : qs('input[name="mpc_bill_addr"]:checked');
      if (!shipAddr || !billAddr) {
        if (err) { err.hidden = false; err.textContent = 'Please select the shipping and billing address.'; }
        return;
      }
      body.shipping_address_id = parseInt(shipAddr.value, 10);
      body.billing_address_id = parseInt(billAddr.value, 10);
    }

    var submitBtn = qs('#mpc-order-submit');
    if (submitBtn) submitBtn.disabled = true;

    fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'text/html',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify(body)
    }).then(function (res) {
      return res.text().then(function (text) { return { ok: res.ok, status: res.status, text: text }; });
    }).then(function (result) {
      if (submitBtn) submitBtn.disabled = false;
      if (!result.ok) {
        var msg = 'Could not create order (HTTP ' + result.status + ').';
        try {
          var parsed = JSON.parse(result.text);
          if (parsed && parsed.message) msg = parsed.message;
        } catch (e) {}
        if (err) { err.hidden = false; err.textContent = msg; }
        return;
      }
      var pane = qs('#chatConversation');
      if (pane) pane.innerHTML = result.text;
      var modal = qs('#mpc-order-modal');
      if (modal) modal.hidden = true;
      scrollBox();
      bindComposer();
      bindQuoteUi();
    }).catch(function () {
      if (submitBtn) submitBtn.disabled = false;
      if (err) { err.hidden = false; err.textContent = 'Network error. Order not created.'; }
    });
  }

  var pickerMode = null;
  var pickerItems = [];

  function closePickerModal() {
    var modal = qs('#mpc-picker-modal');
    if (modal) modal.hidden = true;
  }

  function renderPickerList(items) {
    var list = qs('#mpc-picker-list');
    if (!list) return;
    list.innerHTML = '';
    if (!items.length) {
      list.innerHTML = '<p class="mpc-picker-empty">Nothing found.</p>';
      return;
    }
    items.forEach(function (item, idx) {
      var row = document.createElement('button');
      row.type = 'button';
      row.className = 'mpc-picker-row';
      row.setAttribute('data-idx', String(idx));
      var title = pickerMode === 'order' ? (item.title || ('Order #' + (item.order_number || ''))) : (item.title || '');
      var sub = pickerMode === 'order' ? [item.total, item.status].filter(Boolean).join(' · ') : (item.price || '');
      row.innerHTML = (item.image ? '<img src="' + esc(item.image) + '" alt="">' : '<span class="mpc-picker-noimg"></span>') +
        '<span class="mpc-picker-body"><span class="mpc-picker-title"></span>' +
        '<span class="mpc-picker-sub"></span></span>';
      row.querySelector('.mpc-picker-title').textContent = title;
      row.querySelector('.mpc-picker-sub').textContent = sub;
      list.appendChild(row);
    });
  }

  var pickerSearchDebounce = null;

  function fetchPickerItems(term) {
    var modal = qs('#mpc-picker-modal');
    var url = modal ? modal.getAttribute(pickerMode === 'order' ? 'data-orders-url' : 'data-products-url') : '';
    if (!url) { renderPickerList([]); return; }
    if (term) { url += (url.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(term); }
    fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
      .then(function (res) { return res.json(); })
      .then(function (json) {
        pickerItems = (json && json.data) || [];
        renderPickerList(pickerItems);
      })
      .catch(function () {
        var list = qs('#mpc-picker-list');
        if (list) list.innerHTML = '<p class="mpc-picker-empty">Could not load.</p>';
      });
  }

  function openPickerModal(mode) {
    pickerMode = mode;
    pickerItems = [];
    var modal = qs('#mpc-picker-modal');
    var title = qs('#mpc-picker-title');
    var search = qs('#mpc-picker-search');
    if (title) title.textContent = mode === 'order' ? 'Share an order' : 'Share a product';
    if (search) { search.value = ''; search.hidden = false; search.placeholder = mode === 'order' ? 'Search by order number…' : 'Search…'; }
    renderPickerList([]);
    if (modal) modal.hidden = false;
    fetchPickerItems('');
  }

  function bindPickerModal() {
    var modal = qs('#mpc-picker-modal');
    if (!modal || modal._mpcBound) return;
    modal._mpcBound = true;

    var close = qs('#mpc-picker-close');
    if (close) close.addEventListener('click', closePickerModal);

    var search = qs('#mpc-picker-search');
    if (search) {
      search.addEventListener('input', function () {
        var term = search.value.trim();
        if (pickerSearchDebounce) clearTimeout(pickerSearchDebounce);
        pickerSearchDebounce = setTimeout(function () { fetchPickerItems(term); }, 300);
      });
    }

    var list = qs('#mpc-picker-list');
    if (list) {
      list.addEventListener('click', function (e) {
        var row = e.target.closest('.mpc-picker-row');
        if (!row) return;
        var idx = parseInt(row.getAttribute('data-idx'), 10);
        var item = pickerItems[idx];
        if (!item) return;
        closePickerModal();
        if (pickerMode === 'order') {
          sendExtra('[order_share]' + JSON.stringify(item), 'order_share', item);
        } else {
          sendExtra('[product_share]' + JSON.stringify(item), 'product_share', item);
        }
      });
    }
  }

  function sendExtra(displayText, type, payloadObj) {
    if (sending) return;
    var url = (qs('.mpc-composer') || {}).getAttribute('data-reply-url');
    if (!url) { showError('Missing reply URL. Reload the page.'); return; }

    sending = true;
    showError('');

    var head = qs('.mpc-thread__head');
    var conversationId = head ? (head.getAttribute('data-conversation-id') || String(head.id || '').replace('openChatbox-', '')) : '';
    var customerId = head ? (head.getAttribute('data-customer-id') || '') : '';
    var nowIso = new Date().toISOString();
    var box = qs('#conversationBox');

    ensureDay(nowIso);
    var pending = document.createElement('div');
    pending.innerHTML = bubbleHtml(displayText, true, {
      pending: true,
      createdAt: nowIso,
      time: clock(nowIso),
      type: type,
      payload: payloadObj,
      quoteName: 'You',
      quoteText: displayText
    });
    var pendingNode = pending.firstChild;
    if (box && pendingNode) box.appendChild(pendingNode);
    scrollBox();

    var fd = new FormData();
    fd.append('message', displayText);
    fd.append('type', type);
    if (payloadObj) {
      Object.keys(payloadObj).forEach(function (k) {
        fd.append('payload[' + k + ']', payloadObj[k] == null ? '' : payloadObj[k]);
      });
    }
    fd.append('_token', csrf);

    updateRowPreview(conversationId, customerId, displayText, clock(nowIso));

    fetch(url, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
    }).then(function (res) {
      return res.text().then(function (text) {
        var data = null;
        try { data = JSON.parse(text); } catch (e) {}
        return { ok: res.ok, status: res.status, data: data };
      });
    }).then(function (result) {
      sending = false;
      if (!result.ok) {
        if (pendingNode && pendingNode.parentNode) pendingNode.parentNode.removeChild(pendingNode);
        showError('Could not send (HTTP ' + result.status + '). Try again.');
        return;
      }
      var data = result.data || {};
      if (pendingNode) {
        pendingNode.removeAttribute('data-pending');
        if (data.reply_id) pendingNode.setAttribute('data-reply-id', String(data.reply_id));
        if (data.created_at) pendingNode.setAttribute('data-created-at', data.created_at);
        var t = pendingNode.querySelector('time');
        if (t) t.textContent = data.time || clock(data.created_at);
      }
      scrollBox();
    }).catch(function () {
      sending = false;
      if (pendingNode && pendingNode.parentNode) pendingNode.parentNode.removeChild(pendingNode);
      showError('Network error. Message not sent.');
    });
  }

  function sendReply(form) {
    if (sending) return;
    var ta = qs('textarea[name="message"]', form);
    var fileInput = qs('input[name="photo"]', form);
    var msg = (ta && ta.value ? ta.value : '').trim();
    var hasFile = !!(fileInput && fileInput.files && fileInput.files.length);
    if (!msg && !hasFile) return;

    var url = form.getAttribute('action') || (qs('.mpc-composer') || {}).getAttribute('data-reply-url');
    if (!url) {
      showError('Missing reply URL. Reload the page.');
      return;
    }

    sending = true;
    showError('');
    var btn = qs('#send-btn', form);
    if (btn) btn.disabled = true;

    var head = qs('.mpc-thread__head');
    var conversationId = head ? (head.getAttribute('data-conversation-id') || String(head.id || '').replace('openChatbox-', '')) : '';
    var customerId = head ? (head.getAttribute('data-customer-id') || '') : '';
    var nowIso = new Date().toISOString();
    var box = qs('#conversationBox');

    var quoted = currentQuote();
    ensureDay(nowIso);
    var pending = document.createElement('div');
    pending.innerHTML = bubbleHtml(msg || '[attachment]', true, {
      pending: true,
      createdAt: nowIso,
      time: clock(nowIso),
      quoted: quoted,
      quoteName: 'You',
      quoteText: msg
    });
    var pendingNode = pending.firstChild;
    if (box && pendingNode) box.appendChild(pendingNode);
    scrollBox();

    if (ta) ta.value = '';
    var attachPreview = qs('#mpc-attach-preview');
    if (attachPreview) attachPreview.hidden = true;

    var fd = new FormData();
    fd.append('message', msg);
    fd.append('_token', csrf || (qs('input[name="_token"]', form) || {}).value || '');
    if (quoted && quoted.id) fd.append('parent_id', String(quoted.id));
    if (hasFile) {
      fd.append('photo', fileInput.files[0]);
      fileInput.value = '';
    }

    updateRowPreview(conversationId, customerId, msg || '[Attachment]', clock(nowIso));

    fetch(url, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
    }).then(function (res) {
      return res.text().then(function (text) {
        var data = null;
        try { data = JSON.parse(text); } catch (e) {}
        return { ok: res.ok, status: res.status, data: data, text: text };
      });
    }).then(function (result) {
      sending = false;
      if (btn) btn.disabled = false;
      if (!result.ok) {
        if (pendingNode && pendingNode.parentNode) pendingNode.parentNode.removeChild(pendingNode);
        if (ta) ta.value = msg;
        showError('Could not send (HTTP ' + result.status + '). Try again.');
        return;
      }
      var data = result.data || {};
      clearQuote();
      if (pendingNode) {
        pendingNode.removeAttribute('data-pending');
        if (data.reply_id) pendingNode.setAttribute('data-reply-id', String(data.reply_id));
        if (data.created_at) pendingNode.setAttribute('data-created-at', data.created_at);
        var t = pendingNode.querySelector('time');
        if (t) t.textContent = data.time || clock(data.created_at);
        if (data.attachments && data.attachments.length) {
          var wrap = document.createElement('div');
          wrap.innerHTML = bubbleHtml(data.message || msg, true, {
            replyId: data.reply_id,
            createdAt: data.created_at,
            time: data.time,
            attachments: data.attachments,
            quoted: data.quoted_reply || quoted,
            quoteName: 'You',
            quoteText: data.message || msg
          });
          if (wrap.firstChild) pendingNode.parentNode.replaceChild(wrap.firstChild, pendingNode);
        }
      }
      scrollBox();
    }).catch(function () {
      sending = false;
      if (btn) btn.disabled = false;
      if (pendingNode && pendingNode.parentNode) pendingNode.parentNode.removeChild(pendingNode);
      if (ta) ta.value = msg;
      showError('Network error. Message not sent.');
    });
  }

  qs('#leftsidebar').addEventListener('click', function (e) {
    var row = e.target.closest('.mpc-row');
    if (!row) return;
    loadConversation(row.getAttribute('data-link'), row);
  });

  var search = qs('#mpc-search');
  if (search) {
    search.addEventListener('input', function () {
      var q = search.value.trim().toLowerCase();
      qsa('.mpc-row').forEach(function (row) {
        var name = (row.getAttribute('data-name') || '').toLowerCase();
        var ex = (row.querySelector('.excerpt') || {}).textContent || '';
        row.style.display = (!q || name.indexOf(q) !== -1 || ex.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
      });
    });
  }

  (function initWs() {
    var scheme = @json(config('chat_socket.scheme', 'ws'));
    var host = @json(config('chat_socket.client_host', '127.0.0.1'));
    var port = @json((int) config('chat_socket.port', 6002));
    var path = @json(trim((string) config('chat_socket.client_path', '')));
    host = String(host || '127.0.0.1').replace(/^0\.0\.0\.0/, '127.0.0.1');
    if (path && path.charAt(0) !== '/') path = '/' + path;
    var url = String(scheme || 'ws').replace(/:$/, '') + '://' + host;
    if (path) url += path;
    else if (port && !/:\d+$/.test(host)) url += ':' + port;
    if (!room || typeof WebSocket === 'undefined') return;

    function appendIncoming(result, outgoing) {
      if (result.reply_id && qs('#conversationBox [data-reply-id="' + result.reply_id + '"]')) return;
      ensureDay(result.created_at);
      var box = qs('#conversationBox');
      var wrap = document.createElement('div');
      wrap.innerHTML = bubbleHtml(result.text, outgoing, {
        replyId: result.reply_id,
        createdAt: result.created_at,
        time: result.time,
        attachments: result.attachments,
        quoted: result.quoted_reply,
        quoteName: outgoing ? 'You' : ((qs('.mpc-thread__peer strong') || {}).textContent || ''),
        quoteText: result.text,
        type: result.type,
        payload: result.payload
      });
      if (box && wrap.firstChild) box.appendChild(wrap.firstChild);
      scrollBox();
    }

    function markUnread(row) {
      if (!row) return;
      row.classList.add('is-unread');
      if (!row.querySelector('.mpc-row__dot')) {
        var b = row.querySelector('.mpc-row__bottom');
        if (b) {
          var dot = document.createElement('span');
          dot.className = 'mpc-row__dot';
          b.appendChild(dot);
        }
      }
    }

    function connect() {
      try { socket = new WebSocket(url); } catch (e) { return; }
      socket.onopen = function () {
        socket.send(JSON.stringify({ action: 'subscribe', room: room }));
      };
      socket.onclose = function () { setTimeout(connect, 4000); };
      socket.onmessage = function (ev) {
        var payload;
        try { payload = JSON.parse(ev.data); } catch (e) { return; }
        if (payload && payload.ok && (payload.subscribed || payload.pong)) return;
        if (!payload || payload.event !== 'chat.message') return;
        var result = payload.data || payload.payload || payload;
        if (typeof result === 'string') {
          try { result = JSON.parse(result); } catch (e) { return; }
        }
        var sender = result.sender_type;
        var convId = result.conversation_id != null ? String(result.conversation_id) : '';
        var custId = result.customer_id != null ? String(result.customer_id) : '';

        if (sender === 'merchant') {
          if (result.reply_id && qs('#conversationBox [data-reply-id="' + result.reply_id + '"]')) return;
          var pendingMine = qs('#conversationBox [data-pending="1"]');
          if (pendingMine) {
            if (result.reply_id) pendingMine.setAttribute('data-reply-id', String(result.reply_id));
            pendingMine.removeAttribute('data-pending');
            return;
          }
          if (openMatches(result)) appendIncoming(result, true);
          updateRowPreview(convId, custId, result.text, result.time || clock(result.created_at), result.attachments);
          return;
        }

        if (sender !== 'customer') return;
        if (result.reply_id && qs('#conversationBox [data-reply-id="' + result.reply_id + '"]')) return;

        if (openMatches(result)) {
          appendIncoming(result, false);
        } else {
          markUnread(findRow(convId, custId));
        }
        updateRowPreview(convId, custId, result.text, result.time || clock(result.created_at), result.attachments);
      };
    }
    connect();
  })();
})();
</script>
@endsection
