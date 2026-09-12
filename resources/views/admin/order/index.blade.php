@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.orders') }}
@endsection

@if (is_incevio_package_loaded('ebay') && is_ebay_configured())
  @section('buttons')
    @include('ebay::_pull_btn')
  @endsection
@endif

@section('content')
  @php
    $order_statuses = \App\Helpers\ListHelper::order_statuses();
    $payment_statuses = \App\Helpers\ListHelper::payment_statuses();
    $fulfilment_types = \App\Helpers\ListHelper::fulfilment_types();
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.orders'),
    'icon' => 'fa-shopping-cart',
    'actions' => view('admin.order._mass_actions', compact('order_statuses'))->render(),
    'bodyClass' => 'responsive-table admin-card__body--flush-top',
  ])

  @include('admin.order._filters', compact('order_statuses', 'payment_statuses', 'fulfilment_types'))

  <table class="table table-hover admin-table" id="all-order-table">
    <thead>
      <tr>
        <th class="massActionWrapper">
          <button type="button" class="btn btn-xs btn-default checkbox-toggle">
            <i class="fa fa-square-o" data-toggle="tooltip" data-placement="top" title="{{ trans('app.select_all') }}"></i>
          </button>
        </th>
        <th>{{ trans('app.order_number') }}</th>
        <th>{{ trans('app.order_date') }}</th>
        @if (Auth::user()->isFromPlatform())
          <th>{{ trans('app.shop') }}</th>
        @endif
        <th>{{ trans('app.customer') }}</th>
        <th>{{ trans('app.grand_total') }}</th>
        <th>{{ trans('app.payment_status') }}</th>
        <th>{{ trans('app.order_status') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.options') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea"></tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
