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
                $sharePrefix = '[product_share]';
                if (str_starts_with($lastMessage, $sharePrefix)) {
                    $shared = json_decode(substr($lastMessage, strlen($sharePrefix)), true);
                    $preview = '[Product] '.($shared['title'] ?? 'Shared item');
                } else {
                    $preview = strip_tags($lastMessage);
                }
              @endphp
              <button type="button"
                      class="mpc-row sidebarBody {{ $conversation->isUnread() ? 'is-unread' : '' }}"
                      id="chat-{{ $conversation->customer_id }}"
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
  var socketConnected = false;

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
      var h = d.getHours();
      var m = d.getMinutes();
      var ap = h >= 12 ? 'PM' : 'AM';
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
    if (!msg) {
      el.hidden = true;
      el.textContent = '';
      return;
    }
    el.hidden = false;
    el.textContent = msg;
  }

  function parseShare(text) {
    var prefix = '[product_share]';
    if (!text || text.indexOf(prefix) !== 0) return null;
    try { return JSON.parse(text.slice(prefix.length)); } catch (e) { return null; }
  }

  function bubbleHtml(text, outgoing, meta) {
    meta = meta || {};
    var share = parseShare(text);
    var body = '';
    if (share) {
      body = '<div class="mpc-share"><img src="' + esc(share.image || '') + '" alt=""><div>' +
        '<div class="mpc-share__title">' + esc(share.title || '') + '</div>' +
        '<div class="mpc-share__price">' + esc(share.price || '') + '</div>' +
        '<a href="' + esc(share.url || '#') + '" target="_blank" rel="noopener">View</a></div></div>';
    } else {
      body = '<p class="mpc-bubble__text">' + esc(text || '') + '</p>';
    }
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

  function updateRowPreview(customerId, text, timeLabel) {
    var row = qs('#chat-' + customerId);
    if (!row) return;
    var ex = row.querySelector('.excerpt');
    var tm = row.querySelector('.time-meta');
    if (ex) ex.textContent = String(text || '').replace(/^\[product_share\].*/, '[Product]').slice(0, 72);
    if (tm) tm.textContent = timeLabel || 'just now';
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
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'text/html',
        'X-CSRF-TOKEN': csrf
      }
    }).then(function (res) {
      if (!res.ok) throw new Error('Failed to load conversation (' + res.status + ')');
      return res.text();
    }).then(function (html) {
      pane.innerHTML = html;
      scrollBox();
      bindComposer();
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

    if (backBtn) {
      backBtn.addEventListener('click', function () {
        setThreadOpen(false);
      });
    }

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
    var customerId = head ? head.getAttribute('data-customer-id') : '';
    var nowIso = new Date().toISOString();
    var box = qs('#conversationBox');

    ensureDay(nowIso);
    var pending = document.createElement('div');
    pending.innerHTML = bubbleHtml(msg || '[attachment]', true, { pending: true, createdAt: nowIso, time: clock(nowIso) });
    var pendingNode = pending.firstChild;
    if (box && pendingNode) box.appendChild(pendingNode);
    scrollBox();

    // Clear typed text immediately
    if (ta) ta.value = '';
    var preview = qs('#mpc-attach-preview');
    if (preview) preview.hidden = true;

    var fd = new FormData();
    fd.append('message', msg);
    fd.append('_token', csrf || (qs('input[name="_token"]', form) || {}).value || '');
    if (hasFile) {
      fd.append('photo', fileInput.files[0]);
      fileInput.value = '';
    }

    updateRowPreview(customerId, msg || '[Attachment]', clock(nowIso));

    fetch(url, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf
      }
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
        if (window.console) console.error('[merchant-chat] send failed', result.status, result.text);
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
      if (window.console) console.log('[merchant-chat] sent ok reply_id=', data.reply_id);
    }).catch(function (err) {
      sending = false;
      if (btn) btn.disabled = false;
      if (pendingNode && pendingNode.parentNode) pendingNode.parentNode.removeChild(pendingNode);
      if (ta) ta.value = msg;
      showError('Network error. Message not sent.');
      if (window.console) console.error('[merchant-chat] network error', err);
    });
  }

  // Inbox click
  qs('#leftsidebar').addEventListener('click', function (e) {
    var row = e.target.closest('.mpc-row');
    if (!row) return;
    loadConversation(row.getAttribute('data-link'), row);
  });

  // Search
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

  // WebSocket (optional realtime)
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

    function connect() {
      try { socket = new WebSocket(url); } catch (e) { return; }
      socket.onopen = function () {
        socketConnected = true;
        socket.send(JSON.stringify({ event: 'subscribe', room: room }));
      };
      socket.onclose = function () {
        socketConnected = false;
        setTimeout(connect, 4000);
      };
      socket.onmessage = function (ev) {
        var payload;
        try { payload = JSON.parse(ev.data); } catch (e) { return; }
        if (!payload || payload.event !== 'chat.message') return;
        var result = payload.data || payload.payload || payload;
        if (typeof result === 'string') {
          try { result = JSON.parse(result); } catch (e) { return; }
        }
        var sender = result.sender_type;
        var open = qs('.mpc-thread__head');
        var openId = open ? open.getAttribute('data-customer-id') : null;
        var cid = result.customer_id != null ? String(result.customer_id) : '';

        if (sender === 'merchant') {
          if (result.reply_id && qs('#conversationBox [data-reply-id="' + result.reply_id + '"]')) return;
          var pendingMine = qs('#conversationBox [data-pending="1"]');
          if (pendingMine) {
            if (result.reply_id) pendingMine.setAttribute('data-reply-id', String(result.reply_id));
            pendingMine.removeAttribute('data-pending');
            return;
          }
          if (openId && cid && openId === cid) {
            ensureDay(result.created_at);
            var box = qs('#conversationBox');
            var wrap = document.createElement('div');
            wrap.innerHTML = bubbleHtml(result.text, true, {
              replyId: result.reply_id,
              createdAt: result.created_at,
              time: result.time,
              attachments: result.attachments
            });
            if (box && wrap.firstChild) box.appendChild(wrap.firstChild);
            scrollBox();
          }
          updateRowPreview(cid, result.text, result.time || clock(result.created_at));
          return;
        }

        if (sender !== 'customer') return;

        if (result.reply_id && qs('#conversationBox [data-reply-id="' + result.reply_id + '"]')) return;

        if (openId && cid && openId === cid) {
          ensureDay(result.created_at);
          var box2 = qs('#conversationBox');
          var wrap2 = document.createElement('div');
          wrap2.innerHTML = bubbleHtml(result.text, false, {
            replyId: result.reply_id,
            createdAt: result.created_at,
            time: result.time,
            attachments: result.attachments
          });
          if (box2 && wrap2.firstChild) box2.appendChild(wrap2.firstChild);
          scrollBox();
        } else if (cid) {
          var row = qs('#chat-' + cid);
          if (row) {
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
        }
        updateRowPreview(cid, result.text, result.time || clock(result.created_at));
      };
    }
    connect();
  })();
})();
</script>
@endsection
