@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.cancellations') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.cancellations'),
    'icon' => 'fa-ban',
    'bodyClass' => 'responsive-table',
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.order_number') }}</th>
        <th>{{ trans('app.shop') }}</th>
        <th>{{ trans('app.customer') }}</th>
        <th>{{ trans('app.grand_total') }}</th>
        <th>{{ trans('app.payment') }}</th>
        <th>{{ trans('app.requested_items') }}</th>
        <th>{{ trans('app.requested_at') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($cancellations as $cancellation)
        @if ($cancellation->isOpen())
          <tr>
            <td>
              <a href="{{ route('admin.order.order.show', $cancellation->order) }}">{{ $cancellation->order->order_number }}</a>
              <span class="indent5">{!! $cancellation->order->orderStatus() !!}</span>
              @if ($cancellation->order->disputed)
                <span class="label label-danger indent5">{{ trans('app.statuses.disputed') }}</span>
              @endif
            </td>
            <td>
              <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.show', $cancellation->shop_id) }}" class="ajax-modal-btn">{{ $cancellation->shop->getName() }}</a>
            </td>
            <td>
              <a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.show', $cancellation->customer_id) }}" class="ajax-modal-btn">{{ $cancellation->customer->getName() }}</a>
            </td>
            <td>{{ get_formated_currency($cancellation->order->grand_total, 2, $cancellation->order->currency_id) }}</td>
            <td>{!! $cancellation->order->paymentStatusName() !!}</td>
            <td>{{ $cancellation->items_count . '/' . $cancellation->order->quantity }}</td>
            <td>{{ $cancellation->created_at->diffForHumans() }}</td>
            <td>{!! $cancellation->statusName() !!}</td>
            <td class="row-options admin-row-actions">
              @can('cancel', $cancellation->order)
                <a href="javascript:void(0)" data-link="{{ route('admin.order.cancellation.create', $cancellation->order) }}" class="ajax-modal-btn btn btn-default btn-sm btn-flat">{{ trans('app.approve') }}</a>
              @endcan
            </td>
          </tr>
        @endif
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
