@php
  $orders = isset($orders) ? collect($orders) : collect([$order]);
  $isMultiStore = $orders->count() > 1;
  $firstOrder = $orders->first();
  $is_mpesa_pending = optional($firstOrder->paymentMethod)->code === 'mpesa' && ! $firstOrder->isPaid();
@endphp

<section class="sf-order-confirm">
  <div class="container">
    <div class="sf-order-confirm__hero {{ $is_mpesa_pending ? 'is-pending' : 'is-success' }}">
      <div class="sf-order-confirm__icon" aria-hidden="true">
        @if ($is_mpesa_pending)
          <i class="fas fa-mobile-alt"></i>
        @else
          <i class="fas fa-check"></i>
        @endif
      </div>
      <div class="sf-order-confirm__hero-copy">
        @if ($is_mpesa_pending)
          <h1>@lang('mpesa::lang.complete_on_phone')</h1>
          <p>@lang('mpesa::lang.redirect_when_paid')</p>
        @else
          <h1>@lang('theme.notify.order_placed_thanks')</h1>
          <p>
            @if ($isMultiStore)
              {{ trans('theme.order_confirm_subtitle') }}
              ({{ $orders->count() }} {{ strtolower(trans('theme.stores') ?: 'stores') }})
            @else
              @lang('theme.order_confirm_subtitle')
            @endif
          </p>
        @endif
      </div>
    </div>

    @foreach ($orders as $order)
      @php
        $order_is_mpesa_pending = optional($order->paymentMethod)->code === 'mpesa' && ! $order->isPaid();
        $payment_instructions = null;
        if (optional($order->paymentMethod)->type == \App\Models\PaymentMethod::TYPE_MANUAL) {
            if (vendor_get_paid_directly()) {
                $payment_method = optional($order->shop->config)->manualPaymentMethods
                    ->where('id', $order->payment_method_id)
                    ->first();
                $payment_instructions = optional($payment_method)->pivot->payment_instructions;
            } else {
                $payment_instructions = get_from_option_table('wallet_payment_instructions_' . $order->paymentMethod->code);
            }
        }
      @endphp

      <div class="sf-order-confirm__store-block" style="{{ $isMultiStore ? 'margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(0,0,0,.08);' : '' }}">
        @if ($isMultiStore && $order->shop)
          <h2 class="h4" style="margin-bottom: .75rem;">
            @lang('theme.sold_by'):
            @if ($order->shop->slug)
              <a href="{{ route('show.store', $order->shop->slug) }}">{{ $order->shop->name }}</a>
            @else
              {{ $order->shop->name }}
            @endif
          </h2>
        @endif

        <div class="sf-order-confirm__meta">
          <div class="sf-order-confirm__meta-item">
            <span>@lang('theme.order_id')</span>
            <strong>{{ $order->order_number }}</strong>
          </div>
          <div class="sf-order-confirm__meta-item">
            <span>@lang('theme.payment_status')</span>
            <strong>
              @if ($order_is_mpesa_pending)
                @lang('mpesa::lang.waiting_for_payment')
              @else
                {!! $order->paymentStatusName() !!}
              @endif
            </strong>
          </div>
          <div class="sf-order-confirm__meta-item">
            <span>@lang('theme.order_amount')</span>
            <strong>{{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}</strong>
          </div>
          @if (! $isMultiStore && $order->shop)
            <div class="sf-order-confirm__meta-item">
              <span>@lang('theme.sold_by')</span>
              <strong>
                @if ($order->shop->slug)
                  <a href="{{ route('show.store', $order->shop->slug) }}">{{ $order->shop->name }}</a>
                @else
                  {{ $order->shop->name }}
                @endif
              </strong>
            </div>
          @endif
        </div>

        @if ($payment_instructions)
          <div class="sf-order-confirm__notice">
            <strong>@lang('theme.payment_instruction'):</strong>
            {!! $payment_instructions !!}
          </div>
        @endif

        @include('theme::partials.order_delivery_location', ['order' => $order])

        @if ($order->is_digital)
          <div class="sf-order-confirm__panel">
            <h3>@lang('theme.download')</h3>
            <p class="text-muted">
              @if (\Auth::guard('customer')->check())
                @lang('messages.download_link_loggedin_customer')
              @else
                @lang('messages.download_link_guest_customer')
              @endif
            </p>
            @foreach ($order->inventories as $item)
              <div class="sf-order-confirm__download">
                <h4>{{ trans('theme.download_links_of') . ': ' . $item->title }}</h4>
                <ul>
                  @foreach ($item->attachments as $attachment)
                    @php
                      $downloadUrl = route('order.attachment.download', [
                          'attachment' => $attachment,
                          'order' => $order->id,
                          'inventory' => $item->id,
                      ]);
                    @endphp
                    <li>
                      <a href="{{ $downloadUrl }}">{{ $attachment->name ?? $downloadUrl }}</a>
                      <button type="button" class="btn btn-sm btn-default" onclick="navigator.clipboard.writeText('{{ $downloadUrl }}')">
                        {{ trans('theme.copy_to_clipboard') }}
                      </button>
                    </li>
                  @endforeach
                </ul>
              </div>
            @endforeach
          </div>
        @else
          <div class="sf-order-confirm__panel">
            <h3>@lang('theme.ordered_items')</h3>
            <ul class="sf-order-confirm__items">
              @foreach ($order->inventories as $item)
                <li>
                  <img src="{{ get_product_img_src($item, 'tiny') }}" alt="{{ $item->title }}" loading="lazy">
                  <div>
                    <strong>{{ $item->pivot->item_description ?? $item->title }}</strong>
                    <span>{{ get_formated_currency($item->pivot->unit_price, 2, $order->currency_id) }} × {{ $item->pivot->quantity }}</span>
                  </div>
                </li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="sf-order-confirm__cta">
          @if (\Auth::guard('customer')->check())
            <a class="btn btn-primary" href="{{ route('order.detail', $order) }}">@lang('theme.button.order_detail')</a>
          @endif
          @if ($loop->last)
            @if ($isMultiStore && \Auth::guard('customer')->check())
              <a class="btn btn-default" href="{{ route('account', 'orders') }}">@lang('theme.orders')</a>
            @endif
            <a class="btn btn-default" href="{{ url('/') }}">{{ trans('theme.button.continue_shopping') }}</a>
          @endif
        </div>
      </div>
    @endforeach
  </div>
</section>

@if (config('services.google.gtm_container_id'))
  @include('scripts.dataLayer.order_complete')
@endif
