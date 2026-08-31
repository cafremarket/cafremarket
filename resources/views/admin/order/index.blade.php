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
        <th>{{ trans('app.model.delivery_boy') }}</th>
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

  @include('admin.partials.ui.trash_start', ['title' => trans('app.archived_orders')])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.order_number') }}</th>
        <th>{{ trans('app.order_date') }}</th>
        <th>{{ trans('app.grand_total') }}</th>
        <th>{{ trans('app.payment') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th>{{ trans('app.archived_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($archives as $archive)
        <tr>
          <td>
            @can('view', $archive)
              <a href="{{ route('admin.order.order.show', $archive->id) }}">{{ $archive->order_number }}</a>
            @else
              {{ $archive->order_number }}
            @endcan
          </td>
          <td>{{ $archive->created_at->toDayDateTimeString() }}</td>
          <td>{{ get_formated_currency($archive->grand_total, 2, $archive->currency_id) }}</td>
          <td>{!! $archive->paymentStatusName() !!}</td>
          <td>{!! $archive->orderStatus() !!}</td>
          <td>{{ $archive->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('archive', $archive)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.order.order.restore', $archive->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
            @endcan
            @can('delete', $archive)
              {!! Form::open(['route' => ['admin.order.order.destroy', $archive->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip">
                <i class="fa fa-trash-o"></i>
              </button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
