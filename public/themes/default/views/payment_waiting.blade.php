@extends('theme::layouts.main')

@php
  $methodName = optional(\App\Models\PaymentMethod::where('code', $intent->payment_method)->first())->name ?: strtoupper($intent->payment_method);
@endphp

@section('content')
  <section class="sf-order-confirm__header">
    <div class="container">
      <ol class="breadcrumb nav-breadcrumb mb-0">
        <li><a href="{{ url('/') }}">@lang('theme.home')</a></li>
        <li><a href="{{ route('cart.index') }}">@lang('theme.shopping_cart')</a></li>
        <li class="active">@lang('theme.payment_wait.title')</li>
      </ol>
    </div>
  </section>

  <section class="sf-order-confirm sf-pay-wait" id="sf-pay-wait" data-state="{{ $state['status'] }}">
    <div class="container">
      <div class="sf-order-confirm__hero is-pending" id="sf-pay-hero">
        <div class="sf-order-confirm__icon" aria-hidden="true">
          <i class="fas fa-mobile-alt" id="sf-pay-icon"></i>
        </div>
        <div class="sf-order-confirm__hero-copy">
          <h1 id="sf-pay-title">@lang('theme.payment_wait.title')</h1>
          <p>@lang('theme.payment_wait.subtitle')</p>
        </div>
      </div>

      <div class="sf-pay-wait__status" role="status" aria-live="polite">
        <div class="sf-pay-wait__spinner" id="sf-pay-spinner" aria-hidden="true"></div>
        <div class="sf-pay-wait__status-copy">
          <strong id="sf-pay-message">{{ $state['message'] }}</strong>
          <div class="sf-pay-wait__timer" id="sf-pay-timer-wrap">
            @lang('theme.payment_wait.time_left'): <span id="sf-pay-timer">--:--</span>
          </div>
        </div>
      </div>

      <div class="sf-order-confirm__meta">
        <div class="sf-order-confirm__meta-item">
          <span>@lang('theme.payment_wait.amount')</span>
          <strong>{{ $state['amount_formatted'] }}</strong>
        </div>
        <div class="sf-order-confirm__meta-item">
          <span>@lang('theme.payment_wait.method')</span>
          <strong>{{ $methodName }}</strong>
        </div>
        @if ($state['msisdn'])
          <div class="sf-order-confirm__meta-item">
            <span>@lang('theme.payment_wait.phone')</span>
            <strong>{{ $state['msisdn'] }}</strong>
          </div>
        @endif
        @if ($state['store_count'] > 1)
          <div class="sf-order-confirm__meta-item">
            <span>@lang('theme.payment_wait.stores')</span>
            <strong>{{ $state['store_count'] }}</strong>
          </div>
        @endif
      </div>

      <div class="sf-order-confirm__notice">
        <i class="fas fa-info-circle"></i> @lang('theme.payment_wait.no_order_yet')
      </div>

      <div class="sf-order-confirm__cta">
        <button type="button" class="btn btn-primary" id="sf-pay-check">@lang('theme.payment_wait.check_now')</button>
        <button type="button" class="btn btn-primary hidden" id="sf-pay-retry">@lang('theme.payment_wait.try_again')</button>
        <button type="button" class="btn btn-default" id="sf-pay-cancel">@lang('theme.payment_wait.cancel')</button>
        <a class="btn btn-default hidden" id="sf-pay-cart" href="{{ route('cart.index') }}">@lang('theme.payment_wait.back_to_cart')</a>
      </div>
    </div>
  </section>
@endsection

@section('scripts')
  <style>
    .sf-pay-wait__status {
      display: flex;
      align-items: center;
      gap: 16px;
      background: #fff;
      border: 1px solid #e8edf2;
      border-radius: 16px;
      padding: 18px 20px;
      margin-bottom: 20px;
    }
    .sf-pay-wait__spinner {
      width: 36px;
      height: 36px;
      flex-shrink: 0;
      border-radius: 50%;
      border: 4px solid #fde6c4;
      border-top-color: #d97706;
      animation: sf-pay-spin 0.9s linear infinite;
    }
    .sf-pay-wait__status-copy strong { display: block; color: #0f172a; font-size: 16px; }
    .sf-pay-wait__timer { color: #64748b; font-size: 14px; margin-top: 4px; font-variant-numeric: tabular-nums; }
    .sf-pay-wait.is-done .sf-pay-wait__spinner { display: none; }
    .sf-pay-wait.is-failed .sf-order-confirm__hero { background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%); border-color: #fecaca; }
    .sf-pay-wait.is-failed .sf-order-confirm__icon { background: #dc2626; box-shadow: 0 10px 24px rgba(220, 38, 38, 0.25); }
    .sf-pay-wait.is-paid .sf-order-confirm__hero { background: linear-gradient(135deg, #f3fbf6 0%, #eef6ff 100%); border-color: #d7ebe0; }
    .sf-pay-wait.is-paid .sf-order-confirm__icon { background: #16a34a; box-shadow: 0 10px 24px rgba(22, 163, 74, 0.25); }
    @keyframes sf-pay-spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .sf-pay-wait__spinner { animation-duration: 2.5s; } }
  </style>
  <script>
    (function() {
      var urls = {
        initiate: @json(route('checkout.payment.initiate', $intent)),
        status: @json(route('checkout.payment.status', $intent)),
        cancel: @json(route('checkout.payment.cancel', $intent)),
        retry: @json(route('checkout.payment.retry', $intent))
      };
      var text = {
        failedTitle: @json(trans('theme.payment_wait.failed_title')),
        confirmed: @json(trans('theme.payment_wait.confirmed')),
        cancelConfirm: @json(trans('theme.payment_wait.cancel_confirm')),
        checking: @json(trans('theme.payment_wait.checking'))
      };
      var csrf = @json(csrf_token());
      var state = @json($state);

      var root = document.getElementById('sf-pay-wait');
      var el = {
        title: document.getElementById('sf-pay-title'),
        icon: document.getElementById('sf-pay-icon'),
        message: document.getElementById('sf-pay-message'),
        timer: document.getElementById('sf-pay-timer'),
        timerWrap: document.getElementById('sf-pay-timer-wrap'),
        check: document.getElementById('sf-pay-check'),
        retry: document.getElementById('sf-pay-retry'),
        cancel: document.getElementById('sf-pay-cancel'),
        cart: document.getElementById('sf-pay-cart')
      };

      var deadline = Date.now() + (state.expires_in || 0) * 1000;
      var pollTimer = null;
      var clockTimer = null;
      var inFlight = false;

      function request(method, url) {
        return fetch(url, {
          method: method,
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf
          }
        }).then(function(res) {
          return res.json().then(function(body) {
            if (!res.ok) { throw body; }
            return body;
          });
        });
      }

      function show(node, visible) {
        if (node) node.classList.toggle('hidden', !visible);
      }

      function render(data) {
        state = data;
        if (data.message) el.message.textContent = data.message;
        if (typeof data.expires_in === 'number' && data.expires_in > 0) {
          deadline = Date.now() + data.expires_in * 1000;
        }

        var failed = ['failed', 'expired', 'cancelled'].indexOf(data.status) !== -1;
        var paid = data.status === 'paid' || data.status === 'completed';

        root.classList.toggle('is-failed', failed);
        root.classList.toggle('is-paid', paid);
        root.classList.toggle('is-done', failed || data.status === 'completed');

        if (failed) {
          el.title.textContent = text.failedTitle;
          el.icon.className = 'fas fa-times';
          show(el.timerWrap, false);
        } else if (paid) {
          el.title.textContent = text.confirmed;
          el.icon.className = 'fas fa-check';
          show(el.timerWrap, false);
        }

        show(el.check, !failed && !paid);
        show(el.cancel, !!data.can_cancel);
        show(el.retry, !!data.can_retry);
        show(el.cart, failed);

        if (data.status === 'completed' && data.redirect_url) {
          stop();
          window.location.href = data.redirect_url;
        } else if (failed) {
          stop();
        }
      }

      function poll(force) {
        if (inFlight) return;
        inFlight = true;
        request('GET', urls.status + (force ? '?force=1' : ''))
          .then(render)
          .catch(function() {})
          .then(function() { inFlight = false; });
      }

      function tickClock() {
        var left = Math.max(0, Math.round((deadline - Date.now()) / 1000));
        var m = Math.floor(left / 60);
        var s = left % 60;
        el.timer.textContent = m + ':' + (s < 10 ? '0' : '') + s;
      }

      function stop() {
        if (pollTimer) clearInterval(pollTimer);
        if (clockTimer) clearInterval(clockTimer);
        pollTimer = clockTimer = null;
      }

      function start() {
        tickClock();
        clockTimer = setInterval(tickClock, 1000);
        pollTimer = setInterval(function() { poll(false); }, (state.poll_interval || 4) * 1000);
      }

      el.check.addEventListener('click', function() {
        el.message.textContent = text.checking;
        poll(true);
      });

      el.cancel.addEventListener('click', function() {
        if (!window.confirm(text.cancelConfirm)) return;
        request('POST', urls.cancel).then(render).catch(function() {});
      });

      el.retry.addEventListener('click', function() {
        el.retry.disabled = true;
        request('POST', urls.retry)
          .then(function(body) { window.location.href = body.redirect_url; })
          .catch(function(body) {
            el.retry.disabled = false;
            if (body && body.message) el.message.textContent = body.message;
            if (body && body.redirect_url) window.location.href = body.redirect_url;
          });
      });

      render(state);

      if (['failed', 'expired', 'cancelled', 'completed'].indexOf(state.status) === -1) {
        start();
        // Ask the gateway to push the request to the phone (runs once per payment).
        if (state.status === 'created') {
          request('POST', urls.initiate).then(render).catch(function() { poll(true); });
        }
      }
    })();
  </script>
@endsection
