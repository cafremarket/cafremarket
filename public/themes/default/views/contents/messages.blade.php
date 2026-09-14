@php
  $isLiveChatInbox = $messages instanceof \Illuminate\Support\Collection;
@endphp

@if ($isLiveChatInbox)
  @php
    $customerId = Auth::guard('customer')->id();
  @endphp

  <div class="cpc" id="customer-chatbox"
       data-csrf="{{ csrf_token() }}"
       data-customer-id="{{ $customerId }}">
    <div class="cpc__shell">
      <aside class="cpc__inbox" id="cpc-inbox">
        <div class="cpc__inbox-head">
          <p>{{ $messages->count() }} {{ \Illuminate\Support\Str::plural('conversation', $messages->count()) }} with sellers</p>
        </div>

        <div class="cpc__search">
          <i class="fas fa-search"></i>
          <input type="search" id="cpc-search" placeholder="{{ trans('theme.search') ?? 'Search sellers…' }}" autocomplete="off">
        </div>

        <div class="cpc__list" id="cpc-list">
          @forelse ($messages as $conversation)
            @php
              $shop = $conversation->shop;
              $lastMessage = method_exists($conversation, 'lastMessagePlain')
                  ? (string) $conversation->lastMessagePlain()
                  : (string) ($conversation->message ?? '');
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
              $unread = (int) ($conversation->unread_count ?? 0) > 0;
              $shopName = $shop ? $shop->name : (trans('theme.store') ?? 'Store');
              $shopImage = $shop ? get_logo_url($shop, 'small') : get_logo_url('system', 'logo');
            @endphp
            <button type="button"
                    class="cpc-row {{ $unread ? 'is-unread' : '' }}"
                    id="chat-{{ $conversation->id }}"
                    data-conversation-id="{{ $conversation->id }}"
                    data-shop-id="{{ $conversation->shop_id }}"
                    data-link="{{ route('customer.chat.show', $conversation, false) }}"
                    data-name="{{ $shopName }}">
              <span class="cpc-row__avatar-wrap">
                <img src="{{ $shopImage }}" alt="">
                @if ($unread)
                  <span class="cpc-row__badge">{{ $conversation->unread_count > 9 ? '9+' : $conversation->unread_count }}</span>
                @endif
              </span>
              <span class="cpc-row__body">
                <span class="cpc-row__top">
                  <span class="name-meta">{{ $shopName }}</span>
                  <span class="time-meta">{{ $conversation->updated_at->diffForHumans() }}</span>
                </span>
                <span class="cpc-row__bottom">
                  <span class="excerpt">{{ \Illuminate\Support\Str::limit($preview, 72) }}</span>
                  @if ($unread)
                    <span class="cpc-row__dot" aria-hidden="true"></span>
                  @endif
                </span>
              </span>
            </button>
          @empty
            <div class="cpc__empty-list">
              <i class="fas fa-comments"></i>
              <p>{{ trans('theme.empty_inbox') ?? 'Your inbox is empty' }}</p>
              <span>{{ trans('theme.start_chat_hint') ?? 'Message a seller from a product or store page.' }}</span>
            </div>
          @endforelse
        </div>
      </aside>

      <section class="cpc__thread" id="chatConversation">
        <div class="cpc__placeholder">
          <div class="cpc__placeholder-icon"><i class="fas fa-comments"></i></div>
          <h3>{{ trans('theme.select_conversation') ?? 'Select a conversation' }}</h3>
          <p>{{ trans('theme.select_conversation_hint') ?? 'Pick a seller on the left to continue chatting.' }}</p>
        </div>
      </section>
    </div>
  </div>

  <script>
  (function () {
    'use strict';

    var root = document.getElementById('customer-chatbox');
    if (!root) return;

    var csrf = root.getAttribute('data-csrf') || (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var sending = false;
    var socket = null;
    var activeRoom = '';

    function qs(sel, el) { return (el || document).querySelector(sel); }
    function qsa(sel, el) { return Array.prototype.slice.call((el || document).querySelectorAll(sel)); }

    function esc(s) {
      var d = document.createElement('div');
      d.textContent = s == null ? '' : String(s);
      return d.innerHTML;
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
      root.classList.toggle('cpc--thread-open', !!open);
    }

    function showError(msg) {
      var el = qs('#cpc-send-error');
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

    function bubbleHtml(text, outgoing, meta) {
      meta = meta || {};
      var share = parseShare(text);
      var body = '';
      if (share && share._shareType === 'order') {
        var line = [share.total, share.status].filter(Boolean).join(' · ');
        body = '<div class="cpc-share cpc-share--order">' +
          (share.image ? '<img src="' + esc(share.image) + '" alt="">' : '') +
          '<div><div class="cpc-share__title">' + esc(share.title || ('Order #' + (share.order_number || ''))) + '</div>' +
          (line ? '<div class="cpc-share__price">' + esc(line) + '</div>' : '') +
          '<a href="' + esc(share.url || '#') + '" target="_blank" rel="noopener">View order</a></div></div>';
      } else if (share) {
        body = '<div class="cpc-share"><img src="' + esc(share.image || '') + '" alt=""><div>' +
          '<div class="cpc-share__title">' + esc(share.title || '') + '</div>' +
          '<div class="cpc-share__price">' + esc(share.price || '') + '</div>' +
          '<a href="' + esc(share.url || '#') + '" target="_blank" rel="noopener">View</a></div></div>';
      } else {
        body = '<p class="cpc-bubble__text">' + esc(text || '') + '</p>';
      }
      if (meta.attachments && meta.attachments.length) {
        body += '<div class="cpc-atts">';
        meta.attachments.forEach(function (a) {
          var url = a.url || '';
          var ext = String(a.extension || '').toLowerCase();
          var isImg = ['jpg','jpeg','png','gif','webp'].indexOf(ext) !== -1;
          if (isImg) {
            body += '<a href="' + esc(url) + '" target="_blank" rel="noopener"><img src="' + esc(url) + '" alt=""></a>';
          } else {
            body += '<a href="' + esc(url) + '" target="_blank" rel="noopener" class="cpc-atts__file"><i class="fas fa-paperclip"></i> ' + esc(a.name || 'File') + '</a>';
          }
        });
        body += '</div>';
      }
      var cls = outgoing ? 'cpc-bubble cpc-bubble--out' : 'cpc-bubble cpc-bubble--in';
      var attrs = '';
      if (meta.replyId) attrs += ' data-reply-id="' + esc(meta.replyId) + '"';
      if (meta.pending) attrs += ' data-pending="1"';
      if (meta.createdAt) attrs += ' data-created-at="' + esc(meta.createdAt) + '"';
      return '<div class="' + cls + '"' + attrs + '><div class="cpc-bubble__body">' + body +
        '<time>' + esc(meta.time || clock(meta.createdAt)) + '</time></div></div>';
    }

    function ensureDay(iso) {
      var box = qs('#conversationBox');
      if (!box) return;
      var key = dayKey(iso);
      if (!key) return;
      var days = box.querySelectorAll('.cpc-day');
      var last = days.length ? days[days.length - 1] : null;
      if (last && last.getAttribute('data-day') === key) return;
      var sep = document.createElement('div');
      sep.className = 'cpc-day';
      sep.setAttribute('data-day', key);
      sep.innerHTML = '<span>Today</span>';
      box.appendChild(sep);
    }

    function findRow(conversationId, shopId) {
      var id = conversationId != null && conversationId !== '' ? String(conversationId) : '';
      var sid = shopId != null && shopId !== '' ? String(shopId) : '';
      if (id) {
        var byId = qs('#chat-' + id) || qs('.cpc-row[data-conversation-id="' + id + '"]');
        if (byId) return byId;
      }
      if (sid) {
        return qs('.cpc-row[data-shop-id="' + sid + '"]');
      }
      return null;
    }

    function updateRowPreview(conversationId, shopId, text, timeLabel, attachments) {
      var row = findRow(conversationId, shopId);
      if (!row) return;
      var ex = row.querySelector('.excerpt');
      var tm = row.querySelector('.time-meta');
      if (ex) ex.textContent = previewText(text, attachments).slice(0, 72);
      if (tm) tm.textContent = timeLabel || 'just now';
      var list = qs('#cpc-list');
      if (list && row.parentNode) {
        list.insertBefore(row, list.firstChild);
      }
    }

    function openMatches(result) {
      var open = qs('.cpc-thread__head');
      if (!open) return false;
      var openConv = open.getAttribute('data-conversation-id') || String(open.id || '').replace('openChatbox-', '');
      var openShop = open.getAttribute('data-shop-id') || '';
      var rid = result.conversation_id != null ? String(result.conversation_id) : '';
      var sid = result.shop_id != null ? String(result.shop_id) : '';
      if (rid && openConv && rid === openConv) return true;
      if (sid && openShop && sid === openShop) return true;
      return false;
    }

    function subscribeRoom(room) {
      activeRoom = room || '';
      if (!socket || socket.readyState !== 1 || !activeRoom) return;
      try {
        socket.send(JSON.stringify({ action: 'subscribe', room: activeRoom }));
      } catch (e) {}
    }

    function loadConversation(link, row) {
      if (!link) return;
      var pane = qs('#chatConversation');
      pane.innerHTML = '<div class="cpc__placeholder"><div class="cpc__placeholder-icon"><i class="fas fa-circle-notch fa-spin"></i></div><p>Loading…</p></div>';
      setThreadOpen(true);
      qsa('.cpc-row').forEach(function (r) { r.classList.remove('is-active'); });
      if (row) {
        row.classList.add('is-active');
        row.classList.remove('is-unread');
        var badge = row.querySelector('.cpc-row__badge');
        if (badge) badge.remove();
        var dot = row.querySelector('.cpc-row__dot');
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
        var head = qs('.cpc-thread__head');
        if (head) subscribeRoom(head.getAttribute('data-ws-room') || '');
      }).catch(function (err) {
        pane.innerHTML = '<div class="cpc__placeholder"><h3>Could not open chat</h3><p>' + esc(err.message || 'Error') + '</p></div>';
      });
    }

    function bindComposer() {
      var form = qs('#chat-form');
      if (!form || form._cpcBound) return;
      form._cpcBound = true;

      var fileInput = qs('#customerChatFile', form);
      var preview = qs('#cpc-attach-preview');
      var previewName = qs('#cpc-attach-name');
      var clearBtn = qs('#cpc-attach-clear');
      var backBtn = qs('#cpc-back-list');

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
    }

    function sendReply(form) {
      if (sending) return;
      var ta = qs('textarea[name="message"]', form);
      var fileInput = qs('input[name="photo"]', form);
      var msg = (ta && ta.value ? ta.value : '').trim();
      var hasFile = !!(fileInput && fileInput.files && fileInput.files.length);
      if (!msg && !hasFile) return;

      var url = form.getAttribute('action') || (qs('.cpc-composer') || {}).getAttribute('data-reply-url');
      if (!url) {
        showError('Missing reply URL. Reload the page.');
        return;
      }

      sending = true;
      showError('');
      var btn = qs('#send-btn', form);
      if (btn) btn.disabled = true;

      var head = qs('.cpc-thread__head');
      var conversationId = head ? (head.getAttribute('data-conversation-id') || String(head.id || '').replace('openChatbox-', '')) : '';
      var shopId = head ? (head.getAttribute('data-shop-id') || '') : '';
      var nowIso = new Date().toISOString();
      var box = qs('#conversationBox');

      ensureDay(nowIso);
      var pending = document.createElement('div');
      pending.innerHTML = bubbleHtml(msg || '[attachment]', true, { pending: true, createdAt: nowIso, time: clock(nowIso) });
      var pendingNode = pending.firstChild;
      if (box && pendingNode) box.appendChild(pendingNode);
      scrollBox();

      if (ta) ta.value = '';
      var attachPreview = qs('#cpc-attach-preview');
      if (attachPreview) attachPreview.hidden = true;

      var fd = new FormData();
      fd.append('message', msg);
      fd.append('_token', csrf || (qs('input[name="_token"]', form) || {}).value || '');
      if (hasFile) {
        fd.append('photo', fileInput.files[0]);
        fileInput.value = '';
      }

      updateRowPreview(conversationId, shopId, msg || '[Attachment]', clock(nowIso));

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
              attachments: data.attachments
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

    qs('#cpc-list').addEventListener('click', function (e) {
      var row = e.target.closest('.cpc-row');
      if (!row) return;
      loadConversation(row.getAttribute('data-link'), row);
    });

    var search = qs('#cpc-search');
    if (search) {
      search.addEventListener('input', function () {
        var q = search.value.trim().toLowerCase();
        qsa('.cpc-row').forEach(function (row) {
          var name = (row.getAttribute('data-name') || '').toLowerCase();
          var ex = (row.querySelector('.excerpt') || {}).textContent || '';
          row.style.display = (!q || name.indexOf(q) !== -1 || ex.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
        });
      });
    }

    function markUnread(row) {
      if (!row || row.classList.contains('is-active')) return;
      row.classList.add('is-unread');
      if (!row.querySelector('.cpc-row__dot')) {
        var b = row.querySelector('.cpc-row__bottom');
        if (b) {
          var dot = document.createElement('span');
          dot.className = 'cpc-row__dot';
          b.appendChild(dot);
        }
      }
      if (!row.querySelector('.cpc-row__badge')) {
        var wrap = row.querySelector('.cpc-row__avatar-wrap');
        if (wrap) {
          var badge = document.createElement('span');
          badge.className = 'cpc-row__badge';
          badge.textContent = '!';
          wrap.appendChild(badge);
        }
      }
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
      if (typeof WebSocket === 'undefined') return;

      function appendIncoming(result, outgoing) {
        if (result.reply_id && qs('#conversationBox [data-reply-id="' + result.reply_id + '"]')) return;
        ensureDay(result.created_at);
        var box = qs('#conversationBox');
        var wrap = document.createElement('div');
        wrap.innerHTML = bubbleHtml(result.text, outgoing, {
          replyId: result.reply_id,
          createdAt: result.created_at,
          time: result.time,
          attachments: result.attachments
        });
        if (box && wrap.firstChild) box.appendChild(wrap.firstChild);
        scrollBox();
      }

      function connect() {
        try { socket = new WebSocket(url); } catch (e) { return; }
        socket.onopen = function () {
          if (activeRoom) subscribeRoom(activeRoom);
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
          var shopId = result.shop_id != null ? String(result.shop_id) : '';

          if (sender === 'customer') {
            if (result.reply_id && qs('#conversationBox [data-reply-id="' + result.reply_id + '"]')) return;
            var pendingMine = qs('#conversationBox [data-pending="1"]');
            if (pendingMine) {
              if (result.reply_id) pendingMine.setAttribute('data-reply-id', String(result.reply_id));
              pendingMine.removeAttribute('data-pending');
              return;
            }
            if (openMatches(result)) appendIncoming(result, true);
            updateRowPreview(convId, shopId, result.text, result.time || clock(result.created_at), result.attachments);
            return;
          }

          if (sender !== 'merchant') return;
          if (result.reply_id && qs('#conversationBox [data-reply-id="' + result.reply_id + '"]')) return;

          if (openMatches(result)) {
            appendIncoming(result, false);
          } else {
            markUnread(findRow(convId, shopId));
          }
          updateRowPreview(convId, shopId, result.text, result.time || clock(result.created_at), result.attachments);
        };
      }
      connect();
    })();
  })();
  </script>
@else
  {{-- Classic Message inbox fallback when LiveChat is not available --}}
  @if ($messages->count() > 0)
    @php
      $search_q = isset($search_q) ? $search_q : null;
    @endphp

    <div class="sf-message-list">
      <div class="sf-message-list__count">
        {{ trans('theme.of_total', ['first' => $messages->firstItem(), 'last' => $messages->lastItem(), 'total' => $messages->total()]) . ' ' . trans('theme.my_messages') }}
      </div>

      @foreach ($messages as $message)
        <div class="sf-message-row" id="item_{{ $message->id }}">
          <div class="sf-message-row__shop">
            @if ($message->shop)
              <a href="{{ route('show.store', $message->shop->slug) }}">
                @include('theme::partials._shop_logo_frame', ['shop' => $message->shop, 'frameSize' => 'sm', 'thumbSize' => 'thumbnail', 'fullSize' => 'thumbnail'])
                {!! $message->shop->getQualifiedName(10) !!}
              </a>
            @elseif($message->shop_id)
              {{ trans('theme.store') }}
            @else
              <a href="{{ url('/') }}">
                <img src="{{ get_logo_url('system', 'logo') }}" alt="{{ trans('theme.logo') }}" title="{{ trans('theme.logo') }}">
                {{ get_platform_title() }}
              </a>
            @endif
          </div>

          <div class="sf-message-row__subject">
            <a href="{{ route('message.show', $message) }}" class="{{ $message->isUnread() ? 'unread' : '' }}">
              <span>{!! highlightWords($message->subject, $search_q) !!}</span>
              — {!! highlightWords(\Illuminate\Support\Str::limit(strip_tags($message->lastReply->reply ?? $message->message), max(180 - strlen($message->subject), 0)), $search_q) !!}
            </a>
          </div>

          <div class="sf-message-row__meta">
            @if ($message->replies_count)
              <span class="label label-primary" data-toggle="tooltip" data-placement="top" title="{{ trans('app.replies') }}">{{ $message->replies_count }}</span>
            @endif

            @if ($message->attachments_count)
              <i class="fas fa-paperclip" data-toggle="tooltip" data-placement="top" title="{{ trans('app.attachments') }}"></i>
            @endif

            @if ($message->isUnread())
              {!! $message->statusName() !!}
            @endif

            @if ($message->about())
              {!! $message->about() !!}
            @endif
          </div>

          <div class="sf-message-row__date">
            {{ $message->lastReply ? $message->lastReply->updated_at->diffForHumans() : $message->updated_at->diffForHumans() }}
          </div>

          <div class="sf-message-row__actions">
            @if ($message->order_id)
              <a href="{{ route('order.detail', $message->order_id) }}" data-toggle="tooltip" data-placement="left" data-title="{{ trans('theme.button.order_detail') }}"><i class="fas fa-shopping-cart"></i></a>
            @endif

            @if ($message->product_id)
              <a href="{{ storefront_product_url($message->item) }}" data-toggle="tooltip" data-placement="left" data-title="{{ trans('theme.button.view_product_details') }}"><i class="far fa-external-link"></i></a>
            @endif

            <a href="{{ route('message.archive', $message) }}" class="confirm" data-toggle="tooltip" data-placement="left" data-title="{{ trans('theme.archive') }}"><i class="fas fa-trash-o"></i></a>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div class="sf-empty-state">
      <i class="fas fa-envelope" aria-hidden="true"></i>
      <p>@lang('theme.nothing_found')</p>
    </div>
  @endif

  <div class="row pagenav-wrapper mb-3">
    {{ $messages->links('theme::layouts.pagination') }}
  </div>
@endif
