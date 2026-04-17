@extends('theme::layouts.main')

@section('content')
  <section>
    <div class="container">
      <div class="row mb-5">
        <div class="col-md-6 col-md-offset-3 text-center">
          <p class="lead">
            @if (File::exists(sys_image_path('payment-methods') . 'mpesa.png'))
              <img src="{{ asset(sys_image_path('payment-methods') . 'mpesa.png') }}" class="open-img-md" alt="M-Pesa">
            @else
              M-Pesa
            @endif
          </p>
          <p class="lead mt-4">{{ trans('mpesa::lang.complete_on_phone') }}</p>
          <p class="text-muted" id="wait-msg">
            <span class="fa fa-spinner fa-spin"></span> {{ trans('mpesa::lang.redirect_when_paid') }}
          </p>
          <p class="mt-4">
            <a href="{{ route(\Auth::guard('customer')->check() ? 'customer.account.wallet' : 'merchant.wallet') }}" class="btn btn-default">{{ trans('packages.wallet.back_to_wallet') }}</a>
          </p>
        </div>
      </div>
    </div>
  </section>

  <script>
  (function() {
    var ref = '{{ $ref }}';
    var statusUrl = '{{ url('wallet/deposit/mpesa/status') }}?ref=' + encodeURIComponent(ref);
    var walletUrl = '{{ route(\Auth::guard('customer')->check() ? 'customer.account.wallet' : 'merchant.wallet') }}';
    var pollIntervalMs = 4000;
    var maxWaitMs = 90000;
    var startedAt = Date.now();
    var interval;

    function checkStatus(onPaid) {
      var xhr = new XMLHttpRequest();
      xhr.open('GET', statusUrl, true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            if (data.paid && typeof onPaid === 'function') onPaid();
          } catch (e) {}
        }
      };
      xhr.send();
    }

    function onPaid() {
      if (interval) clearInterval(interval);
      interval = null;
      var el = document.getElementById('wait-msg');
      if (el) el.innerHTML = '<span class="fa fa-check text-success"></span> {{ trans('packages.wallet.payment_success') }}';
      window.location.href = walletUrl;
    }

    function tick() {
      if (Date.now() - startedAt >= maxWaitMs) {
        if (interval) clearInterval(interval);
        interval = null;
        var el = document.getElementById('wait-msg');
        if (el) el.innerHTML = '<span class="text-danger">{{ trans('packages.wallet.poll_timeout_error') }}</span>';
        return;
      }
      checkStatus(onPaid);
    }

    tick();
    interval = setInterval(tick, pollIntervalMs);
  })();
  </script>
@endsection
