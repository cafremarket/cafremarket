@extends('theme::layouts.main')

@section('content')
  @include('theme::headers.order_detail')
  @include('theme::contents.order_complete')
  <p id="mpesa-wait-msg" class="text-center text-muted my-3"></p>
  @include('theme::sections.recent_views')
@endsection

@section('scripts')
  @include('scripts.order_transaction_schema')
  <script>
  (function() {
    var statusUrl = '{{ url('mpesa/' . $order->id . '/status') }}';
    var completeUrl = '{{ url('mpesa/' . $order->id . '/complete') }}';
    var pollIntervalMs = 4000;
    var maxWaitMs = 90000;
    var startedAt = Date.now();
    var interval;
    var waitEl = document.getElementById('mpesa-wait-msg');

    function checkStatus() {
      var xhr = new XMLHttpRequest();
      xhr.open('GET', statusUrl, true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            if (data.paid) {
              if (interval) clearInterval(interval);
              window.location.href = completeUrl;
            }
          } catch (e) {}
        }
      };
      xhr.send();
    }

    function tick() {
      if (Date.now() - startedAt >= maxWaitMs) {
        if (interval) clearInterval(interval);
        if (waitEl) waitEl.innerHTML = '<span class="text-danger">{{ trans('mpesa::lang.poll_timeout_error') }}</span>';
        return;
      }
      checkStatus();
    }

    tick();
    interval = setInterval(tick, pollIntervalMs);
  })();
  </script>
@endsection
