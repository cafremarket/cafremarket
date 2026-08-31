@php
  $order_statuses = $order_statuses ?? \App\Helpers\ListHelper::order_statuses();
  $payment_statuses = $payment_statuses ?? \App\Helpers\ListHelper::payment_statuses();
  $fulfilment_types = $fulfilment_types ?? \App\Helpers\ListHelper::fulfilment_types();
@endphp

<div class="admin-filters admin-filters--inset">
  <div class="admin-filters__controls">
    <select id="filter-all-order-table-order-status" class="form-control input-sm">
      <option value="0" selected>{{ trans('app.placeholder.filter_by_order_status') }}</option>
      <option value="0">{{ trans('app.all_orders') }}</option>
      @foreach ($order_statuses as $order_status_number => $order_status)
        <option value="{{ $order_status_number }}">{{ $order_status }}</option>
      @endforeach
    </select>

    <select id="filter-all-order-table-payment-status" class="form-control input-sm">
      <option value="0" selected>{{ trans('app.placeholder.filter_by_status') }}</option>
      <option value="0">{{ trans('app.all_orders') }}</option>
      @foreach ($payment_statuses as $payment_status_number => $payment_status)
        <option value="{{ $payment_status_number }}">{{ $payment_status }}</option>
      @endforeach
    </select>

    <select id="filter-all-order-table-fulfilment-status" class="form-control input-sm">
      <option value="0" selected>{{ trans('app.placeholder.fulfilment_type') }}</option>
      <option value="0">{{ trans('app.all_orders') }}</option>
      @foreach ($fulfilment_types as $fulfilment_type_value => $fulfilment_type_name)
        <option value="{{ $fulfilment_type_value }}">{{ $fulfilment_type_name }}</option>
      @endforeach
    </select>
  </div>
</div>
