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
