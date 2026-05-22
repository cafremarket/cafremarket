@extends('theme::layouts.main')

@section('content')
  <section>
    <div class="container">
      <div class="row mb-5">
        <div class="col-md-6 col-md-offset-3 text-center">
          @if (Session::has('warning'))
            <div class="alert alert-warning">{{ Session::get('warning') }}</div>
          @endif
          @if (Session::has('error'))
            <div class="alert alert-danger">{{ Session::get('error') }}</div>
          @endif

          <p class="lead">
            @if (File::exists(sys_image_path('payment-methods') . 'emola.png'))
              <img src="{{ asset(sys_image_path('payment-methods') . 'emola.png') }}" class="open-img-md" alt="eMola">
            @else
              eMola
            @endif
          </p>
          <p class="lead mt-4">{{ trans('packages.wallet.emola_complete_on_phone') }}</p>
          @include('wallet::partials.wallet_deposit_complete_summary', ['depositSummary' => $depositSummary ?? null])
          <p class="text-muted" id="wait-msg">
            <span class="fa fa-spinner fa-spin"></span> {{ trans('packages.wallet.emola_redirect_when_paid') }}
          </p>

          @if (!empty($canResend))
            <div class="panel panel-default text-left mt-4">
              <div class="panel-body">
                <p class="text-muted mb-3">@lang('theme.emola_resend_help')</p>
                {!! Form::open(['route' => 'wallet.deposit.emola.resend', 'method' => 'POST', 'class' => 'emola-wallet-resend-form', 'id' => 'emola-wallet-resend-form']) !!}
                  {!! Form::hidden('ref', $ref) !!}
                  <div class="form-group">
                    <label for="emola-resend-number-wallet" class="control-label">@lang('theme.emola_number')</label>
                    {!! Form::text('emola_number', old('emola_number'), [
                        'id' => 'emola-resend-number-wallet',
                        'class' => 'form-control',
                        'placeholder' => trans('theme.emola_number_placeholder'),
                        'inputmode' => 'numeric',
                        'maxlength' => 9,
                        'required' => 'required',
                        'pattern' => '^(86|87)[0-9]{7}$',
                    ]) !!}
                  </div>
                  <div class="form-group">
                    {!! Form::button('<i class="fa fa-refresh"></i> ' . trans('theme.emola_resend_button'), [
                        'type' => 'button',
                        'class' => 'btn btn-primary btn-block emola-wallet-resend-submit',
                        'data-confirm' => trans('theme.emola_resend_confirm'),
                    ]) !!}
                    <button type="button" class="btn btn-default btn-block emola-wallet-sync-payment" id="emola-wallet-sync-btn">
                      <i class="fa fa-search"></i> @lang('theme.emola_check_payment')
                    </button>
                  </div>
                {!! Form::close() !!}
                <p class="help-block small text-muted mb-0">@lang('theme.emola_number_help')</p>
              </div>
            </div>
          @endif

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
    var statusUrl = '{{ url('wallet/deposit/emola/status') }}?ref=' + encodeURIComponent(ref);
    var walletUrl = '{{ route(\Auth::guard('customer')->check() ? 'customer.account.wallet' : 'merchant.wallet') }}';
    var pollIntervalMs = 4000;
    var maxWaitMs = 120000;
    var startedAt = Date.now();
    var interval;

    function checkStatus(onPaid, force) {
      var url = statusUrl + (force ? '&force=1' : '');
      var xhr = new XMLHttpRequest();
      xhr.open('GET', url, true);
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
      checkStatus(onPaid, false);
    }

    var resendBtn = document.querySelector('.emola-wallet-resend-submit');
    if (resendBtn) {
      resendBtn.addEventListener('click', function() {
        var msg = resendBtn.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) return;
        document.getElementById('emola-wallet-resend-form').submit();
      });
    }

    var syncBtn = document.getElementById('emola-wallet-sync-btn');
    if (syncBtn) {
      syncBtn.addEventListener('click', function() {
        syncBtn.disabled = true;
        var el = document.getElementById('wait-msg');
        if (el) el.innerHTML = '<span class="fa fa-spinner fa-spin"></span> {{ trans('packages.wallet.checking_status') }}';
        checkStatus(function() {
          onPaid();
        }, true);
        setTimeout(function() { syncBtn.disabled = false; }, 3000);
      });
    }

    tick();
    interval = setInterval(tick, pollIntervalMs);
  })();
  </script>
@endsection
