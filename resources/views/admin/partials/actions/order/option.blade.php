<td class="row-options">
  {{-- Platform admins get read-only access to orders — no fulfill/archive shortcuts.
       Those actions belong to the merchant on their own panel. --}}
  @unless (Auth::user()->isFromPlatform())
    @can('fulfill', $order)
      @unless ($order->isFulfilled())
        @if ($order->deliver())
          <a href="javascript:void(0)" data-link="{{ route('admin.order.order.fulfillment', $order) }}" class="ajax-modal-btn">
            <i data-toggle="tooltip" data-placement="top" title="{{ trans('app.fulfill_order_delivery') }}" class="fa fa-truck"></i>
          </a>&nbsp;
        @elseif ($order->pickup())
          {!! Form::open(['route' => ['admin.order.order.markAsPickedUp', $order->id], 'method' => 'put', 'class' => 'inline']) !!}
          <button type="submit" class="confirm ajax-silent" style="background:none;border:none;padding:0;" data-confirm="{{ trans('app.confirm_picked_up') }}">
            <i data-toggle="tooltip" data-placement="top" title="{{ trans('app.fulfill_order_pickup') }}" class="fa fa-shopping-basket"></i>
          </button>
          {!! Form::close() !!}&nbsp;
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

  @unless (Auth::user()->isFromPlatform())
    @can('archive', $order)
      {!! Form::open([
          'route' => ['admin.order.order.archive', $order->id],
          'method' => 'delete',
          'class' => 'data-form',
      ]) !!}

      {!! Form::button('<i class="fa fa-archive text-muted"></i>', [
          'type' => 'submit',
          'class' => 'confirm ajax-silent',
          'title' => trans('app.order_archive'),
          'data-toggle' => 'tooltip',
          'data-placement' => 'top',
      ]) !!}

      {!! Form::close() !!}
    @endcan
  @endunless
</td>
