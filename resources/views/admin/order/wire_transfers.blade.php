@extends('admin.layouts.master')

@section('page_title')
  {{ trans('nav.wire_transfers') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('nav.wire_transfers'),
    'icon' => 'fa-bank',
    'bodyClass' => 'responsive-table',
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.order_number') }}</th>
        <th>{{ trans('app.shop') }}</th>
        <th>{{ trans('app.customer') }}</th>
        <th>{{ trans('app.grand_total') }}</th>
        <th>{{ trans('theme.payment_proof') }}</th>
        <th>{{ trans('app.requested_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($orders as $order)
        <tr>
          <td>
            <a href="{{ route('admin.order.order.show', $order->id) }}">{{ $order->order_number }}</a>
            <span class="indent5">{!! $order->orderStatus() !!}</span>
          </td>
          <td>{{ $order->shop->getName() }}</td>
          <td>{{ $order->customer->getName() }}</td>
          <td>{{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}</td>
          <td>
            @forelse ($order->attachments as $attachment)
              @php
                $isImage = in_array(strtolower((string) $attachment->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
              @endphp
              <a href="{{ route('attachment.download', $attachment) }}"><i class="fa fa-file"></i> {{ $attachment->name }}</a>
              @if ($isImage)
                <a href="{{ route('attachment.view', $attachment) }}" target="_blank" class="btn btn-xs btn-default">{{ trans('app.preview') }}</a>
              @endif
              <br>
            @empty
              @if ($order->wire_transfer_proof_path)
                <a href="{{ \Illuminate\Support\Facades\Storage::url($order->wire_transfer_proof_path) }}" target="_blank">
                  <i class="fa fa-file"></i> {{ $order->wire_transfer_proof_name ?: basename($order->wire_transfer_proof_path) }}
                </a>
              @else
                <span class="text-muted">&mdash;</span>
              @endif
            @endforelse
          </td>
          <td>{{ $order->created_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('fulfill', $order)
              {!! Form::open(['route' => ['admin.order.wireTransfers.approve', $order], 'method' => 'put', 'class' => 'inline']) !!}
              <button type="submit" class="confirm btn btn-default btn-sm btn-flat"><i class="fa fa-check"></i> {{ trans('app.mark_as_paid') }}</button>
              {!! Form::close() !!}
            @endcan
            <a href="{{ route('admin.order.order.show', $order->id) }}" class="btn btn-default btn-sm btn-flat">{{ trans('app.open') }}</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="text-center">{{ trans('messages.no_orders') }}</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
