{{-- Customer's overall feedback for this order (one order, one feedback). --}}
@include('admin.partials.ui.card_start', [
  'title' => trans('app.order_feedback'),
  'icon' => 'fa-star',
  'bodyClass' => 'admin-order-sidebar-panel',
])
  @if ($order->orderFeedback)
    <div class="text-warning" style="font-size:16px;" title="{{ $order->orderFeedback->rating }}/5">
      @for ($i = 1; $i <= 5; $i++)
        <i class="fa {{ $i <= $order->orderFeedback->rating ? 'fa-star' : 'fa-star-o' }}"></i>
      @endfor
      <span class="text-muted small" style="margin-left:6px;">{{ $order->orderFeedback->rating }}/5</span>
    </div>
    @if ($order->orderFeedback->comment)
      <p style="margin:8px 0 0; white-space:pre-line;">{{ $order->orderFeedback->comment }}</p>
    @endif
    <small class="text-muted">
      {{ optional($order->customer)->getName() }} &middot; {{ $order->orderFeedback->created_at->diffForHumans() }}
    </small>
  @else
    <p class="text-muted" style="margin:0;">{{ trans('app.no_order_feedback_yet') }}</p>
  @endif
@include('admin.partials.ui.card_end')
