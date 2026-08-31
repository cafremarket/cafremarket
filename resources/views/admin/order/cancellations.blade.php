@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.cancellations') }}
@endsection

@can('create', \App\Models\Order::class)
  @section('buttons')
    <a href="javascript:void(0)" data-link="{{ route('admin.order.order.searchCustomer') }}" class="ajax-modal-btn btn btn-new btn-flat">{{ trans('app.add_order') }}</a>
  @endsection
@endcan

@section('content')
  @include('admin.partials.ui.card_tabbed_start', [
    'title' => trans('app.cancellations'),
    'icon' => 'fa-ban',
  ])

    <ul class="nav nav-tabs nav-justified admin-tabs">
      <li class="{{ Request::has('tab') ? '' : 'active' }}">
        <a href="#open_tab" data-toggle="tab">{{ trans('app.open') }}</a>
      </li>
      <li class="{{ Request::input('tab') == 'archived' ? 'active' : '' }}">
        <a href="#archived_tab" data-toggle="tab"><i class="fa fa-archive hidden-sm"></i> {{ trans('app.archived') }}</a>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane {{ Request::has('tab') ? '' : 'active' }} responsive-table" id="open_tab">
        <table class="table table-hover admin-table table-no-sort">
          <thead>
            <tr>
              <th>{{ trans('app.order_number') }}</th>
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
                  <td>{{ get_formated_currency($cancellation->order->grand_total, 2, $cancellation->order->currency_id) }}</td>
                  <td>{!! $cancellation->order->paymentStatusName() !!}</td>
                  <td>{{ $cancellation->items_count . '/' . $cancellation->order->quantity }}</td>
                  <td>{{ $cancellation->created_at->diffForHumans() }}</td>
                  <td>
                    @if ($cancellation->inReview())
                      <span class="label label-info">{!! trans('app.waiting_for_approval') !!}</span>
                    @else
                      {!! $cancellation->statusName() !!}
                    @endif
                  </td>
                  <td class="row-options admin-row-actions">
                    @can('cancel', $cancellation->order)
                      @if ($cancellation->isNew())
                        {!! Form::open(['route' => ['admin.order.cancellation.handle', $cancellation->order, 'approve'], 'method' => 'put', 'class' => 'inline']) !!}
                        <button class="btn btn-default btn-sm btn-flat confirm" type="submit"><i class="fa fa-check"></i> {{ trans('app.approve') }}</button>
                        {!! Form::close() !!}
                        {!! Form::open(['route' => ['admin.order.cancellation.handle', $cancellation->order, 'decline'], 'method' => 'put', 'class' => 'inline']) !!}
                        <button class="btn btn-danger btn-sm btn-flat confirm" type="submit"><i class="fa fa-times"></i> {{ trans('app.decline') }}</button>
                        {!! Form::close() !!}
                      @endif
                    @endcan
                  </td>
                </tr>
              @endif
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="tab-pane {{ Request::input('tab') == 'archived' ? 'active' : '' }} responsive-table" id="archived_tab">
        <table class="table table-hover admin-table table-no-sort">
          <thead>
            <tr>
              <th>{{ trans('app.order_number') }}</th>
              <th>{{ trans('app.grand_total') }}</th>
              <th>{{ trans('app.payment') }}</th>
              <th>{{ trans('app.requested_items') }}</th>
              <th>{{ trans('app.requested_at') }}</th>
              <th>{{ trans('app.status') }}</th>
              <th class="admin-table__actions-col">&nbsp;</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($cancellations as $cancellation)
              @unless ($cancellation->isOpen())
                <tr>
                  <td>
                    <a href="{{ route('admin.order.order.show', $cancellation->order) }}">{{ $cancellation->order->order_number }}</a>
                    <span class="indent5">{!! $cancellation->order->orderStatus() !!}</span>
                    @if ($cancellation->order->disputed)
                      <span class="label label-danger indent5">{{ trans('app.statuses.disputed') }}</span>
                    @endif
                  </td>
                  <td>{{ get_formated_currency($cancellation->order->grand_total, 2, $cancellation->order->currency_id) }}</td>
                  <td>{!! $cancellation->order->paymentStatusName() !!}</td>
                  <td>{{ $cancellation->items_count . '/' . $cancellation->order->quantity }}</td>
                  <td>{{ $cancellation->created_at->diffForHumans() }}</td>
                  <td>{!! $cancellation->statusName() !!}</td>
                  <td></td>
                </tr>
              @endunless
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

  @include('admin.partials.ui.card_tabbed_end')
@endsection
