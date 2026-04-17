@extends('theme::layouts.main')

@section('content')
  @include('theme::headers.order_detail')

  <section>
    <div class="container">
      <div class="row mb-5">
        <div class="col-md-6 col-md-offset-3 text-center">
          @if (File::exists(sys_image_path('payment-methods') . 'mpesa.png'))
            <img src="{{ asset(sys_image_path('payment-methods') . 'mpesa.png') }}" class="open-img-md" alt="mpesa">
          @else
            <p class="lead">M-Pesa</p>
          @endif

          <p class="lead mt-4">@lang('mpesa::lang.complete_on_phone')</p>
          <p class="text-muted" id="wait-msg">
            <span class="fa fa-spinner fa-spin"></span> @lang('mpesa::lang.redirect_when_paid')
          </p>
        </div>
      </div>
    </div>
  </section>

  <script>
  (function() {
    var statusUrl = '{{ url('mpesa/' . $order->id . '/status') }}';
    var completeUrl = '{{ url('mpesa/' . $order->id . '/complete') }}';
    var interval = setInterval(function() {
      var xhr = new XMLHttpRequest();
      xhr.open('GET', statusUrl, true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            if (data.paid) {
              clearInterval(interval);
              var el = document.getElementById('wait-msg');
              if (el) el.innerHTML = '<span class="fa fa-check text-success"></span> {{ trans('mpesa::lang.payment_detected') }}';
              window.location.href = completeUrl;
            }
          } catch (e) {}
        }
      };
      xhr.send();
    }, 3000);
  })();
  </script>
@endsection
