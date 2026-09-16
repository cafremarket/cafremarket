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
    if (share && share._shareType === 'order') {
      var line = [share.total, share.status].filter(Boolean).join(' · ');
      body = '<div class="mpc-share mpc-share--order">' +
        (share.image ? '<img src="' + esc(share.image) + '" alt="">' : '') +
        '<div><div class="mpc-share__title">' + esc(share.title || ('Order #' + (share.order_number || ''))) + '</div>' +
        (line ? '<div class="mpc-share__price">' + esc(line) + '</div>' : '') +
        '<a href="' + esc(share.url || '#') + '" target="_blank" rel="noopener">View order</a></div></div>';
    } else if (share) {
      body = '<div class="mpc-share"><img src="' + esc(share.image || '') + '" alt=""><div>' +
        '<div class="mpc-share__title">' + esc(share.title || '') + '</div>' +
        '<div class="mpc-share__price">' + esc(share.price || '') + '</div>' +
        '<a href="' + esc(share.url || '#') + '" target="_blank" rel="noopener">View</a></div></div>';
    } else {
      body = '<p class="mpc-bubble__text">' + esc(text || '') + '</p>';
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
    bindQuoteUi();
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
        quoteText: result.text
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
