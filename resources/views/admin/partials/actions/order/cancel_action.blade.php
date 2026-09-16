@can('cancel', $order)
  @unless ($order->isCanceled())
    <a href="javascript:void(0)" data-link="{{ route('admin.order.cancellation.create', $order) }}" class="ajax-modal-btn admin-action-btn" title="{{ trans('app.cancel_order') }}" data-toggle="tooltip">
      <i class="fa fa-times-circle text-warning"></i>
    </a>
  @endunless
@endcan
