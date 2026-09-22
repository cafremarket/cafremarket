{{--
  One order, one feedback. Shows the customer's submitted feedback for the order,
  or the rating form when the order is delivered and not rated yet.
  Params: $order (required), $compact (bool, optional — tighter layout for order list cards)
--}}
@php
  $orderFeedback = $order->orderFeedback;
  $canGiveOrderFeedback = !$orderFeedback && $order->canGiveOrderFeedback();
  $compact = $compact ?? false;
  // Not delivered yet (and not canceled): tell the customer rating unlocks on delivery.
  $feedbackPending = !$orderFeedback && !$canGiveOrderFeedback && !$order->isCanceled() && !$order->isDelivered();
@endphp

@if ($feedbackPending)
  <div class="sf-order-feedback sf-order-feedback--pending {{ $compact ? 'sf-order-feedback--compact' : '' }}">
    <i class="fa fa-star-o" aria-hidden="true"></i> @lang('theme.order_feedback_not_allowed')
  </div>
@endif

@if ($orderFeedback || $canGiveOrderFeedback)
  <div class="sf-order-feedback {{ $compact ? 'sf-order-feedback--compact' : '' }}" id="order-feedback-{{ $order->id }}">
    @if ($orderFeedback)
      <div class="sf-order-feedback__head">
        <strong><i class="fa fa-comment-o" aria-hidden="true"></i> @lang('theme.your_order_feedback')</strong>
        <span class="sf-order-feedback__stars" aria-label="{{ $orderFeedback->rating }}/5">
          @for ($i = 1; $i <= 5; $i++)
            <i class="fa {{ $i <= $orderFeedback->rating ? 'fa-star' : 'fa-star-o' }}"></i>
          @endfor
        </span>
        <small class="text-muted">{{ $orderFeedback->created_at->diffForHumans() }}</small>
      </div>
      @if ($orderFeedback->comment)
        <p class="sf-order-feedback__comment">{{ $orderFeedback->comment }}</p>
      @endif
    @else
      <form action="{{ route('order.orderFeedback.save', $order) }}" method="POST" class="sf-order-feedback__form">
        @csrf
        <div class="sf-order-feedback__head">
          <strong><i class="fa fa-star-o" aria-hidden="true"></i> @lang('theme.rate_this_order')</strong>
          <div class="sf-order-feedback__rating" role="radiogroup" aria-label="@lang('theme.rate_this_order')">
            @for ($i = 5; $i >= 1; $i--)
              <input type="radio" id="of-{{ $order->id }}-{{ $i }}" name="rating" value="{{ $i }}" required>
              <label for="of-{{ $order->id }}-{{ $i }}" title="{{ $i }}/5"><i class="fa fa-star"></i></label>
            @endfor
          </div>
        </div>
        @unless ($compact)
          <p class="text-muted small mb-2">@lang('theme.rate_this_order_help')</p>
        @endunless
        <textarea name="comment" class="form-control" rows="{{ $compact ? 2 : 3 }}" maxlength="1000" placeholder="{{ trans('theme.order_feedback_placeholder') }}"></textarea>
        <button type="submit" class="btn sf-btn-primary btn-sm mt-2">@lang('theme.submit_order_feedback')</button>
      </form>
    @endif
  </div>

@endif

@if ($orderFeedback || $canGiveOrderFeedback || $feedbackPending)
  @once
    <style>
      .sf-order-feedback { padding: 12px 16px; border-top: 1px solid #eef2f7; background: #f8fafc; }
      .sf-order-feedback--compact { font-size: .9rem; }
      .sf-order-feedback--pending { color: #64748b; }
      .sf-order-feedback__head { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; }
      .sf-order-feedback__stars { color: #f59e0b; }
      .sf-order-feedback__comment { margin: 6px 0 0; white-space: pre-line; color: #334155; }
      .sf-order-feedback__form textarea { margin-top: 8px; resize: vertical; }
      .sf-order-feedback__rating { display: inline-flex; flex-direction: row-reverse; }
      .sf-order-feedback__rating input { position: absolute; opacity: 0; width: 1px; height: 1px; }
      .sf-order-feedback__rating label { cursor: pointer; color: #cbd5e1; font-size: 1.3rem; padding: 0 2px; margin: 0; }
      .sf-order-feedback__rating label:hover,
      .sf-order-feedback__rating label:hover ~ label,
      .sf-order-feedback__rating input:checked ~ label { color: #f59e0b; }
      .sf-order-feedback__rating input:focus-visible + label { outline: 2px solid #f59e0b; border-radius: 3px; }
    </style>
  @endonce
@endif
