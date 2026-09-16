<td class="row-options">
  {{-- Platform admins get read-only access to orders — no fulfill shortcuts.
       Those actions belong to the merchant on their own panel. --}}
  @unless (Auth::user()->isFromPlatform())
    @can('fulfill', $order)
      @unless ($order->isFulfilled())
        @if ($order->deliver())
          {{-- One modal: Delivery Boy OR Courier (exclusive) --}}
          <a href="javascript:void(0)" data-link="{{ route('admin.order.deliveryboys', $order->id) }}" class="ajax-modal-btn">
            <i data-toggle="tooltip" data-placement="top" title="{{ trans('app.fulfill_order') }}" class="fa fa-truck"></i>
          </a>&nbsp;
        @elseif ($order->pickup())
          {{-- Picking up in person requires the customer's OTP, entered on the order
               detail page — no one-click shortcut, same as the courier/delivery-boy flow. --}}
          <a href="{{ panel_route('admin.order.order.show', $order->id) }}">
            <i data-toggle="tooltip" data-placement="top" title="{{ trans('app.confirm_pickup_otp') }}" class="fa fa-shopping-basket"></i>
          </a>&nbsp;
        @endif
      @endunless
    @endcan
  @endunless

  @can('cancel', $order)
    @unless ($order->isCanceled())
      <a href="javascript:void(0)" data-link="{{ route('admin.order.cancellation.create', $order) }}" class="ajax-modal-btn">
        <i data-toggle="tooltip" data-placement="top" title="{{ trans('app.cancel_order') }}" class="fa fa-times-circle text-warning"></i>
      </a>&nbsp;
    @endunless
  @endcan

  @if ($order->refunds->where('status', \App\Models\Refund::STATUS_NEW)->count())
    @can('index', \App\Models\Refund::class)
      <a href="{{ route('admin.refunds.index', ['tab' => 'pending', 'q' => $order->order_number]) }}" title="{{ trans('nav.refunds') }}" data-toggle="tooltip">
        <i class="fa fa-undo text-warning"></i>
      </a>&nbsp;
    @endcan
  @endif

  <a href="{{ panel_route('admin.order.order.show', $order->id) }}">
    <i data-toggle="tooltip" data-placement="top" title="{{ trans('app.open') }}" class="fa fa-expand"></i>
  </a>&nbsp;

  <a href="{{ panel_route('admin.order.order.invoice', $order) }}">
    <i data-toggle="tooltip" data-placement="top" title="{{ trans('app.download_invoice') }}" class="fa fa-download"></i>
  </a>&nbsp;

  <a href="{{ panel_route('admin.order.shipping_label', $order) }}">
    <i data-toggle="tooltip" data-placement="top" title="{{ trans('app.download_shipping_label') }}" class="fa fa-file"></i>
  </a>&nbsp;
</td>
