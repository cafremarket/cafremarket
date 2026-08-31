@php
  $order_statuses = $order_statuses ?? \App\Helpers\ListHelper::order_statuses();
@endphp

<div class="btn-group">
  <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    {{ trans('app.assign_payment_status') }}
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu" role="menu">
    <li><a href="javascript:void(0)" data-link="{{ route('admin.order.order.assignPaymentStatus', 'paid') }}" class="massAction" data-doafter="reload">{{ trans('app.mark_as_paid') }}</a></li>
    <li><a href="javascript:void(0)" data-link="{{ route('admin.order.order.assignPaymentStatus', 'unpaid') }}" class="massAction" data-doafter="reload">{{ trans('app.mark_as_unpaid') }}</a></li>
    <li><a href="javascript:void(0)" data-link="{{ route('admin.order.order.assignPaymentStatus', 'refunded') }}" class="massAction" data-doafter="reload">{{ trans('app.mark_as_refunded') }}</a></li>
  </ul>
</div>

<div class="btn-group">
  <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    {{ trans('app.assign_order_status') }}
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu" role="menu">
    @foreach ($order_statuses as $order_status_number => $order_status)
      <li><a href="javascript:void(0)" data-link="{{ route('admin.order.order.assignOrderStatus', $order_status_number) }}" class="massAction" data-doafter="reload">{{ $order_status }}</a></li>
    @endforeach
  </ul>
</div>
