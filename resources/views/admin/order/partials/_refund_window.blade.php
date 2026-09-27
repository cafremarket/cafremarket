{{-- Refund/return window of one order item. Pass: $order, $item (order inventory with pivot). --}}
@unless ($order->isCanceled())
  @php $refundWindow = \App\Services\Orders\RefundWindow::forItem($order, $item); @endphp
  <br><small class="{{ $refundWindow['status'] === 'open' ? 'text-success' : 'text-muted' }}">
    <i class="fa fa-undo"></i> {{ $refundWindow['label'] }}
  </small>
@endunless
