@php
  $hasOtp = ! empty($order->otp);
  $hasCourierDetails = $order->hasCourier();
  $isPickup = $order->pickup();
  // The pickup address/map itself is shown by order_delivery_location.blade.php —
  // this partial only needs to know it's a pickup order to label the OTP correctly.
@endphp

@if ($hasOtp || $hasCourierDetails)
  <div class="sf-order-proof">
    @if ($hasCourierDetails)
      <div class="sf-order-proof__card">
        <span class="sf-order-location__label">@lang('theme.courier_details')</span>
        <div class="sf-order-proof__courier">
          <strong>{{ $order->courier_name }}</strong>
          @if ($order->courier_phone)
            <div>{{ $order->courier_phone }}</div>
          @endif
          @if ($order->courier_tracking_number)
            <div class="text-muted small">@lang('theme.tracking_id'): {{ $order->courier_tracking_number }}</div>
          @endif
        </div>
        @if ($order->courier_phone)
          <a class="btn btn-default btn-sm mt-2" href="tel:{{ $order->courier_phone }}">
            <i class="fas fa-phone"></i> @lang('theme.call_courier')
          </a>
        @endif
      </div>
    @endif

    @if ($hasOtp)
      <div class="sf-order-proof__card sf-order-proof__otp">
        <span class="sf-order-location__label">@lang($isPickup ? 'app.pickup_otp' : 'theme.delivery_otp')</span>
        <p class="sf-order-proof__otp-help">
          @if ($isPickup)
            @lang('app.pickup_otp_help')
          @elseif ($hasCourierDetails)
            @lang('theme.delivery_otp_help_courier')
          @else
            @lang('theme.delivery_otp_help_rider')
          @endif
        </p>
        <div class="sf-order-proof__otp-code">{{ $order->otp }}</div>
      </div>
    @endif

    @if ($hasCourierDetails && ! $order->isDelivered())
      <div class="sf-order-proof__card sf-order-proof__confirm">
        {!! Form::open(['route' => ['goods.received', $order], 'method' => 'put']) !!}
        <button type="submit" class="btn btn-new btn-block" onclick="return confirm(@js(trans('theme.confirm_received_prompt')));">
          <i class="fas fa-check-circle"></i> @lang('theme.confirm_received')
        </button>
        {!! Form::close() !!}
      </div>
    @endif
  </div>

  <style>
    .sf-order-proof { display: flex; flex-wrap: wrap; gap: 12px; margin: 12px 0 20px; }
    .sf-order-proof__card { flex: 1 1 220px; border: 1px solid #e6e6e6; border-radius: 8px; padding: 14px 16px; background: #fff; }
    .sf-order-proof__otp-code { font-size: 28px; font-weight: 700; letter-spacing: 4px; margin-top: 6px; }
    .sf-order-proof__otp-help { font-size: 13px; color: #6b7280; margin: 4px 0 0; }
    .sf-order-proof__confirm { display: flex; align-items: center; justify-content: center; }
  </style>
@endif
