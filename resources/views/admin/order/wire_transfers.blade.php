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
      @foreach ($orders as $order)
        <tr>
          <td>
            <a href="{{ route('admin.order.order.show', $order->id) }}">{{ $order->order_number }}</a>
            <span class="indent5">{!! $order->orderStatus() !!}</span>
            @if ($order->wire_transfer_rejected_at)
              <br>
              <span class="label label-danger" data-toggle="tooltip" title="{{ $order->wire_transfer_rejection_reason }}">
                {{ trans('app.rejected') }} &mdash; {{ $order->wire_transfer_rejected_at->diffForHumans() }}
              </span>
            @endif
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

              <button type="button" class="btn btn-danger btn-sm btn-flat" data-toggle="modal" data-target="#rejectWireModal{{ $order->id }}">
                <i class="fa fa-times"></i> {{ trans('app.reject') }}
              </button>
            @endcan
            <a href="{{ route('admin.order.order.show', $order->id) }}" class="btn btn-default btn-sm btn-flat">{{ trans('app.open') }}</a>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Reject modals live outside the table entirely — a <table>/<tbody> may
       only contain row elements; a <div> nested inside <tbody> gets silently
       hoisted out by the browser's HTML parser, which was also confusing
       DataTables' column detection on this table (table-no-sort auto-inits
       DataTables — see footer_js.blade.php). --}}
  @foreach ($orders as $order)
    @can('fulfill', $order)
      <div class="modal fade" id="rejectWireModal{{ $order->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            {!! Form::open(['route' => ['admin.order.wireTransfers.reject', $order], 'method' => 'put']) !!}
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <h4 class="modal-title">{{ trans('app.reject') }} &mdash; {{ $order->order_number }}</h4>
            </div>
            <div class="modal-body">
              <div class="form-group">
                {!! Form::label('reason', trans('app.reason') . ':*') !!}
                {!! Form::textarea('reason', null, ['class' => 'form-control', 'rows' => 3, 'required', 'placeholder' => trans('messages.notice.wire_transfer_rejection_reason_placeholder')]) !!}
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">{{ trans('app.cancel') }}</button>
              {!! Form::submit(trans('app.reject'), ['class' => 'btn btn-danger btn-flat']) !!}
            </div>
            {!! Form::close() !!}
          </div>
        </div>
      </div>
    @endcan
  @endforeach

  @include('admin.partials.ui.card_end')
@endsection
