@can('cancel', $order)
  @unless ($order->isCanceled())
    @if ($order->cancellationFeeApplicable() || cancellation_require_admin_approval())
      <a href="javascript:void(0)" data-link="{{ route('admin.order.cancellation.create', $order) }}" class="ajax-modal-btn admin-action-btn" title="{{ trans('app.cancel_order') }}" data-toggle="tooltip">
        <i class="fa fa-times-circle text-warning"></i>
      </a>
    @else
      {!! Form::open(['route' => ['admin.order.order.cancel', $order], 'method' => 'put', 'class' => 'inline']) !!}
      <button type="submit" class="confirm ajax-silent admin-action-btn" style="padding:0;border:0;background:transparent;" title="{{ trans('app.cancel_order') }}" data-toggle="tooltip">
        <i class="fa fa-times-circle text-warning"></i>
      </button>
      {!! Form::close() !!}
    @endif
  @endunless
@endcan
