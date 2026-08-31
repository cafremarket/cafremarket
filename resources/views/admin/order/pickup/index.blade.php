@extends('admin.layouts.master')

@section('page_title')
  {{ trans('theme.pickup') }} {{ trans('app.orders') }}
@endsection

@if (Auth::user()->isFromMerchant())
  @can('create', \App\Models\Order::class)
    @section('buttons')
      <a href="javascript:void(0)" data-link="{{ route('admin.order.order.searchCustomer') }}" class="ajax-modal-btn btn btn-new btn-flat">{{ trans('app.add_order') }}</a>
    @endsection
  @endcan
@endif

@section('content')
  @php
    $unpaid_orders = $orders->where('payment_status', '<', \App\Models\Order::PAYMENT_STATUS_PAID);
  @endphp

  @include('admin.partials.ui.card_tabbed_start', [
    'title' => trans('theme.pickup') . ' ' . trans('app.orders'),
    'icon' => 'fa-shopping-bag',
  ])

    <ul class="nav nav-tabs nav-justified admin-tabs">
      <li class="{{ Request::has('tab') ? '' : 'active' }}">
        <a href="#all_orders_tab" data-toggle="tab">
          <i class="fa fa-shopping-cart hidden-sm"></i>
          {{ trans('app.all_orders') }}
        </a>
      </li>
      <li class="{{ Request::input('tab') == 'unpaid' ? 'active' : '' }}">
        <a href="#unpaid_tab" data-toggle="tab">
          <i class="fa fa-money hidden-sm"></i>
          {{ trans('app.statuses.unpaid') }}
        </a>
      </li>
      <li class="{{ Request::input('tab') == 'unfulfilled' ? 'active' : '' }}">
        <a href="#unfulfilled_tab" data-toggle="tab">
          <i class="fa fa-shopping-basket hidden-sm"></i>
          {{ trans('app.statuses.unfulfilled') }}
        </a>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane {{ Request::has('tab') ? '' : 'active' }} responsive-table" id="all_orders_tab">
        <table class="table table-hover admin-table table-no-sort">
          <thead>
            <tr>
              <th>{{ trans('app.order_number') }}</th>
              <th>{{ trans('app.order_date') }}</th>
              @if (Auth::user()->isFromPlatform())
                <th>{{ trans('app.shop') }}</th>
              @endif
              <th>{{ trans('app.customer') }}</th>
              <th>{{ trans('app.grand_total') }}</th>
              <th>{{ trans('app.payment') }}</th>
              <th>{{ trans('app.status') }}</th>
              <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($orders as $order)
              <tr>
                <td>
                  @can('view', $order)
                    <a href="{{ route('admin.order.order.show', $order->id) }}">{{ $order->order_number }}</a>
                  @else
                    {{ $order->order_number }}
                  @endcan
                  @if ($order->dispute)
                    <span class="label label-danger">{{ trans('app.statuses.disputed') }}</span>
                  @endif
                </td>
                <td>{{ $order->created_at->toDayDateTimeString() }}</td>
                @if (Auth::user()->isFromPlatform())
                  <td>{{ $order->shop->getName() }}</td>
                @endif
                <td>{{ $order->customer->getName() }}</td>
                <td>{{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}</td>
                <td>{!! $order->paymentStatusName() !!}</td>
                <td>{!! $order->orderStatus() !!}</td>
                <td class="row-options admin-row-actions">
                  @can('archive', $order)
                    {!! Form::open(['route' => ['admin.order.order.archive', $order->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
                    <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.order_archive') }}" data-toggle="tooltip"><i class="fa fa-archive"></i></button>
                    {!! Form::close() !!}
                  @endcan
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="tab-pane {{ Request::input('tab') == 'unpaid' ? 'active' : '' }} responsive-table" id="unpaid_tab">
        <table class="table table-hover admin-table table-no-sort">
          <thead>
            <tr>
              <th>{{ trans('app.order_number') }}</th>
              <th>{{ trans('app.order_date') }}</th>
              <th>{{ trans('app.customer') }}</th>
              <th>{{ trans('app.grand_total') }}</th>
              <th>{{ trans('app.payment') }}</th>
              <th>{{ trans('app.status') }}</th>
              <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($unpaid_orders as $order)
              <tr>
                <td>
                  @can('view', $order)
                    <a href="{{ route('admin.order.order.show', $order->id) }}">{{ $order->order_number }}</a>
                  @else
                    {{ $order->order_number }}
                  @endcan
                </td>
                <td>{{ $order->created_at->toDayDateTimeString() }}</td>
                <td>{{ $order->customer->name }}</td>
                <td>{{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}</td>
                <td>{!! $order->paymentStatusName() !!}</td>
                <td>{!! $order->orderStatus() !!}</td>
                <td class="row-options admin-row-actions">
                  @can('archive', $order)
                    {!! Form::open(['route' => ['admin.order.order.archive', $order->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
                    <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.order_archive') }}" data-toggle="tooltip"><i class="fa fa-archive"></i></button>
                    {!! Form::close() !!}
                  @endcan
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="tab-pane {{ Request::input('tab') == 'unfulfilled' ? 'active' : '' }} responsive-table" id="unfulfilled_tab">
        <table class="table table-hover admin-table table-no-sort">
          <thead>
            <tr>
              <th>{{ trans('app.order_number') }}</th>
              <th>{{ trans('app.order_date') }}</th>
              <th>{{ trans('app.customer') }}</th>
              <th>{{ trans('app.grand_total') }}</th>
              <th>{{ trans('app.payment') }}</th>
              <th>{{ trans('app.status') }}</th>
              <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($orders as $order)
              @unless ($order->isFulfilled())
                <tr>
                  <td>
                    @can('view', $order)
                      <a href="{{ route('admin.order.order.show', $order->id) }}">{{ $order->order_number }}</a>
                    @else
                      {{ $order->order_number }}
                    @endcan
                  </td>
                  <td>{{ $order->created_at->toDayDateTimeString() }}</td>
                  <td>{{ $order->customer->name }}</td>
                  <td>{{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}</td>
                  <td>{!! $order->paymentStatusName() !!}</td>
                  <td>{!! $order->orderStatus() !!}</td>
                  <td class="row-options admin-row-actions">
                    @can('archive', $order)
                      {!! Form::open(['route' => ['admin.order.order.archive', $order->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
                      <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.order_archive') }}" data-toggle="tooltip"><i class="fa fa-archive"></i></button>
                      {!! Form::close() !!}
                    @endcan
                  </td>
                </tr>
              @endunless
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

  @include('admin.partials.ui.card_tabbed_end')

  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

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
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
