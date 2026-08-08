{{-- WebSocket live status checker (merchant Configurations tab) --}}
@php
  $wsScheme = config('chat_socket.scheme', 'wss');
  $wsHost = config('chat_socket.client_host', '');
  $wsPort = (int) config('chat_socket.port', 6002);
  $wsPath = trim((string) config('chat_socket.client_path', ''));
  if ($wsPath !== '' && $wsPath[0] !== '/') {
      $wsPath = '/'.$wsPath;
  }
  // Client URL:
  // - with path → wss://host/path
  // - wss without path → wss://host  (443 via nginx subdomain)
  // - ws without path → ws://host:port
  if ($wsPath !== '') {
      $wsUrl = $wsScheme.'://'.$wsHost.$wsPath;
  } elseif (in_array($wsScheme, ['wss', 'https'], true)) {
      $wsUrl = $wsScheme.'://'.$wsHost;
  } elseif ($wsPort > 0) {
      $wsUrl = $wsScheme.'://'.$wsHost.':'.$wsPort;
  } else {
      $wsUrl = $wsScheme.'://'.$wsHost;
  }

  $vendorRoom = '';
  try {
      $vendorRoom = function_exists('get_vendor_chat_room_id') ? (string) get_vendor_chat_room_id() : '';
  } catch (\Throwable $e) {
      $vendorRoom = '';
  }
@endphp

<div class="row" style="padding: 20px 10px;">
  <div class="col-md-10 col-md-offset-1">
    <div class="box box-solid" style="border: 1px solid #e5e7eb; border-radius: 6px;">
      <div class="box-header with-border">
        <h3 class="box-title">
          <i class="fa fa-plug"></i>
          {{ trans('app.websocket_status') }}
        </h3>
      </div>
      <div class="box-body">
        <p class="text-muted" style="margin-bottom: 18px;">
          {{ trans('help.websocket_status_check') }}
        </p>

        <table class="table table-bordered" style="margin-bottom: 16px;">
          <tr>
            <th style="width: 28%;">{{ trans('app.websocket_url') }}</th>
            <td><code id="ws-check-url">{{ $wsUrl }}</code></td>
          </tr>
          <tr>
            <th>{{ trans('app.websocket_room') }}</th>
            <td><code id="ws-check-room">{{ $vendorRoom !== '' ? $vendorRoom : '—' }}</code></td>
          </tr>
          <tr>
            <th>{{ trans('app.status') }}</th>
            <td>
              <span id="ws-check-badge" class="label label-default">{{ trans('app.websocket_idle') }}</span>
              <span id="ws-check-latency" class="text-muted" style="margin-left: 8px;"></span>
            </td>
          </tr>
          <tr>
            <th>{{ trans('app.detail') }}</th>
            <td><span id="ws-check-detail" class="text-muted">{{ trans('help.websocket_click_test') }}</span></td>
          </tr>
        </table>

        <button type="button" id="ws-check-btn" class="btn btn-primary btn-flat">
          <i class="fa fa-refresh"></i>
          {{ trans('app.test_websocket') }}
        </button>
        <button type="button" id="ws-check-stop" class="btn btn-default btn-flat" style="display:none;">
          {{ trans('app.cancel') }}
        </button>

        <pre id="ws-check-log" style="margin-top: 16px; max-height: 220px; overflow: auto; background: #111827; color: #e5e7eb; padding: 12px; border-radius: 4px; font-size: 12px; display: none;"></pre>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  var WS_URL = @json($wsUrl);
  var ROOM = @json($vendorRoom);
  var socket = null;
  var startedAt = 0;

  function $(id) { return document.getElementById(id); }

  function setBadge(kind, text) {
    var el = $('ws-check-badge');
    el.className = 'label label-' + kind;
    el.textContent = text;
  }

  function logLine(msg) {
    var pre = $('ws-check-log');
    pre.style.display = 'block';
    var ts = new Date().toISOString().substr(11, 12);
    pre.textContent += '[' + ts + '] ' + msg + '\n';
    pre.scrollTop = pre.scrollHeight;
  }

  function cleanup() {
    if (socket) {
      try { socket.onopen = socket.onmessage = socket.onerror = socket.onclose = null; socket.close(); } catch (e) {}
      socket = null;
    }
    $('ws-check-stop').style.display = 'none';
    $('ws-check-btn').disabled = false;
  }

  function runTest() {
    cleanup();
    $('ws-check-log').textContent = '';
    $('ws-check-log').style.display = 'block';
    $('ws-check-latency').textContent = '';
    $('ws-check-detail').textContent = '';
    setBadge('warning', @json(trans('app.websocket_connecting')));
    logLine('Connecting to ' + WS_URL);

    if (!WS_URL || WS_URL.indexOf('://') === -1) {
      setBadge('danger', @json(trans('app.websocket_failed')));
      $('ws-check-detail').textContent = @json(trans('help.websocket_bad_config'));
      logLine('Invalid CHAT_SOCKET_* config');
      return;
    }

    startedAt = Date.now();
    $('ws-check-btn').disabled = true;
    $('ws-check-stop').style.display = 'inline-block';

    try {
      socket = new WebSocket(WS_URL);
    } catch (e) {
      setBadge('danger', @json(trans('app.websocket_failed')));
      $('ws-check-detail').textContent = (e && e.message) ? e.message : String(e);
      logLine('Constructor error: ' + (e && e.message ? e.message : e));
      cleanup();
      return;
    }

    var timeout = setTimeout(function() {
      setBadge('danger', @json(trans('app.websocket_timeout')));
      $('ws-check-detail').textContent = @json(trans('help.websocket_timeout_hint'));
      logLine('Timed out after 8s (check SSL / nginx Upgrade headers / Node on :6002)');
      cleanup();
    }, 8000);

    socket.onopen = function() {
      var ms = Date.now() - startedAt;
      logLine('OPEN in ' + ms + 'ms');
      $('ws-check-latency').textContent = ms + ' ms';
      if (ROOM) {
        logLine('Subscribe room=' + ROOM);
        socket.send(JSON.stringify({ action: 'subscribe', room: ROOM }));
      } else {
        clearTimeout(timeout);
        setBadge('success', @json(trans('app.websocket_connected')));
        $('ws-check-detail').textContent = @json(trans('help.websocket_connected_no_room'));
        logLine('Connected (no shop room to subscribe)');
      }
    };

    socket.onmessage = function(ev) {
      var parsed;
      try { parsed = JSON.parse(ev.data); } catch (e) {
        logLine('Raw: ' + String(ev.data).substring(0, 120));
        return;
      }
      logLine('Message: ' + JSON.stringify(parsed).substring(0, 200));
      if (parsed && parsed.ok && parsed.subscribed) {
        clearTimeout(timeout);
        var ms = Date.now() - startedAt;
        setBadge('success', @json(trans('app.websocket_connected')));
        $('ws-check-latency').textContent = ms + ' ms';
        $('ws-check-detail').textContent = @json(trans('help.websocket_subscribed_ok')) + ' (' + parsed.subscribed + ')';
        logLine('SUBSCRIBED OK');
        // Keep open briefly then close so status stays clear
        setTimeout(function() { cleanup(); setBadge('success', @json(trans('app.websocket_connected'))); }, 1500);
      }
      if (parsed && parsed.ok && parsed.pong) {
        logLine('PONG');
      }
    };

    socket.onerror = function() {
      logLine('ERROR event');
      setBadge('danger', @json(trans('app.websocket_failed')));
      $('ws-check-detail').textContent = @json(trans('help.websocket_error_hint'));
    };

    socket.onclose = function(ev) {
      clearTimeout(timeout);
      logLine('CLOSE code=' + ev.code + ' reason=' + (ev.reason || ''));
      if ($('ws-check-badge').textContent.indexOf(@json(trans('app.websocket_connected'))) === -1) {
        setBadge('danger', @json(trans('app.websocket_failed')));
        if (!$('ws-check-detail').textContent) {
          $('ws-check-detail').textContent = @json(trans('help.websocket_closed_hint'));
        }
      }
      $('ws-check-stop').style.display = 'none';
      $('ws-check-btn').disabled = false;
      socket = null;
    };
  }

  document.addEventListener('DOMContentLoaded', function() {
    var btn = $('ws-check-btn');
    var stop = $('ws-check-stop');
    if (btn) btn.addEventListener('click', runTest);
    if (stop) stop.addEventListener('click', function() {
      logLine('Stopped by user');
      cleanup();
      setBadge('default', @json(trans('app.websocket_idle')));
    });

    // Auto-run once when tab becomes visible
    if (window.jQuery) {
      jQuery(document).on('shown.bs.tab', 'a[href="#websocket-tab"]', function() {
        if ($('ws-check-badge').textContent === @json(trans('app.websocket_idle'))) {
          runTest();
        }
      });
    }
  });
})();
</script>
