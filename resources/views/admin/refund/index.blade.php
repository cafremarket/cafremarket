@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.refunds') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.refunds'),
    'icon' => 'fa-undo',
    'actions' => view('admin.refund._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-option">
    <thead>
      <tr>
        <th>{{ trans('app.order_number') }}</th>
        <th>{{ trans('app.return_goods') }}</th>
        <th>{{ trans('app.order_amount') }}</th>
        <th>{{ trans('app.refund_amount') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th>{{ trans('app.created_at') }}</th>
        <th>{{ trans('app.updated_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($refunds as $refund)
        <tr>
          <td>
            @can('index', \App\Models\Order::class)
              <a href="{{ route('admin.order.order.show', $refund->order_id) }}">{{ $refund->order->order_number }}</a>
            @else
              {{ $refund->order->order_number }}
            @endcan
          </td>
          <td>{!! get_yes_or_no($refund->return_goods) !!}</td>
          <td>{{ get_formated_currency($refund->order->grand_total, 2, $refund->order->currency_id) }}</td>
          <td>{{ get_formated_currency($refund->amount, 2, $refund->order->currency_id) }}</td>
          <td>{!! $refund->statusName() !!}</td>
          <td>{{ $refund->created_at->diffForHumans() }}</td>
          <td>{{ $refund->updated_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @if ($refund->order->customer_id)
              @can('index', \App\Models\Customer::class)
                <a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.show', $refund->order->customer_id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.customer') }}" data-toggle="tooltip"><i class="fa fa-user"></i></a>
              @endcan
            @endif
            @can('approve', $refund)
              <a href="javascript:void(0)" data-link="{{ route('admin.support.refund.response', $refund) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.response') }}" data-toggle="tooltip"><i class="fa fa-random"></i></a>
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

  <table class="table table-hover admin-table table-option">
    <thead>
      <tr>
        <th>{{ trans('app.order_number') }}</th>
        <th>{{ trans('app.return_goods') }}</th>
        <th>{{ trans('app.order_amount') }}</th>
        <th>{{ trans('app.refund_amount') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th>{{ trans('app.created_at') }}</th>
        <th>{{ trans('app.updated_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($closed as $refund)
        <tr>
          <td>
            @can('index', \App\Models\Order::class)
              <a href="{{ route('admin.order.order.show', $refund->order_id) }}">{{ $refund->order->order_number }}</a>
            @else
              {{ $refund->order->order_number }}
            @endcan
          </td>
          <td>{!! get_yes_or_no($refund->return_goods) !!}</td>
          <td>{{ get_formated_currency($refund->order->total, 2, $refund->order->currency_id) }}</td>
          <td>{{ get_formated_currency($refund->amount, 2, $refund->order->currency_id) }}</td>
          <td>{!! $refund->statusName() !!}</td>
          <td>{{ $refund->created_at->diffForHumans() }}</td>
          <td>{{ $refund->updated_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @if ($refund->order->customer_id)
              @can('index', \App\Models\Customer::class)
                <a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.show', $refund->order->customer_id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.customer') }}" data-toggle="tooltip"><i class="fa fa-user"></i></a>
              @endcan
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
