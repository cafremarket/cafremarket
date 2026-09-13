@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.disputes') }}
@endsection

@section('content')
  @if (isset($pendingClose) && $pendingClose->count())
    @include('admin.partials.ui.card_start', [
      'title' => trans('app.close_requests'),
      'icon' => 'fa-hourglass-half',
      'bodyClass' => 'responsive-table',
    ])
    <table class="table table-hover admin-table table-no-sort">
      <thead>
        <tr>
          <th>{{ trans('app.ticket') }}</th>
          <th>{{ trans('app.customer') }}</th>
          <th>{{ trans('app.type') }}</th>
          <th>{{ trans('app.requested_by') }}</th>
          <th>{{ trans('app.updated_at') }}</th>
          <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($pendingClose as $dispute)
          <tr>
            <td>
              <strong>{{ $dispute->ticketRef() }}</strong>
              {!! $dispute->statusName() !!}
            </td>
            <td>
              <strong>{{ $dispute->customer->getName() }}</strong>
              @if (Auth::user()->isFromPlatform() && $dispute->shop)
                <br><span class="text-muted">{{ trans('app.vendor') . ': ' . optional($dispute->shop)->name }}</span>
              @endif
              @if ($dispute->order)
                <br><span class="text-muted">{{ trans('app.order') }}: {{ $dispute->order->order_number }}</span>
              @endif
            </td>
            <td>
              <a href="{{ route('admin.support.dispute.show', $dispute->id) }}">{{ optional($dispute->dispute_type)->detail }}</a>
            </td>
            <td>{{ ucfirst($dispute->close_requested_by ?: '-') }}</td>
            <td>{{ $dispute->updated_at->diffForHumans() }}</td>
            <td class="row-options admin-row-actions">
              <a href="{{ route('admin.support.dispute.show', $dispute->id) }}" class="admin-action-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
              @can('close', $dispute)
                {!! Form::open(['route' => ['admin.support.dispute.close', $dispute], 'style' => 'display:inline']) !!}
                {!! Form::button('<i class="fa fa-lock"></i>', ['type' => 'submit', 'class' => 'admin-action-btn confirm', 'title' => trans('app.close_dispute'), 'data-toggle' => 'tooltip']) !!}
                {!! Form::close() !!}
              @endcan
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    @include('admin.partials.ui.card_end')
  @endif

  @include('admin.partials.ui.card_start', [
    'title' => Auth::user()->isFromPlatform() ? trans('app.open_dispute_tickets') : trans('app.disputes'),
    'icon' => 'fa-ticket',
    'bodyClass' => 'responsive-table',
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.ticket') }}</th>
        <th>{{ trans('app.customer') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.raised_by') }}</th>
        <th>{{ trans('app.refund_requested') }}</th>
        <th>{{ trans('app.response') }}</th>
        <th>{{ trans('app.updated_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($disputes as $dispute)
        <tr>
          <td>
            <strong>{{ $dispute->ticketRef() }}</strong>
            {!! $dispute->statusName() !!}
          </td>
          <td>
            <div class="admin-table__shop-cell">
              <img src="{{ get_avatar_src($dispute->customer, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt="">
              <div>
                <strong>{{ $dispute->customer->getName() }}</strong>
                @if (Auth::user()->isFromPlatform() && $dispute->shop)
                  <br><span class="text-muted">{{ trans('app.vendor') . ': ' . optional($dispute->shop)->name }}</span>
                @endif
                @if ($dispute->order)
                  <br><span class="text-muted">{{ trans('app.order') }}: {{ $dispute->order->order_number }}</span>
                @endif
              </div>
            </div>
          </td>
          <td>
            <a href="{{ route('admin.support.dispute.show', $dispute->id) }}">{{ optional($dispute->dispute_type)->detail }}</a>
          </td>
          <td>{{ $dispute->raisedByLabel() }}</td>
          <td>{{ get_formated_currency($dispute->refund_amount, 2, optional($dispute->order)->currency_id) }}</td>
          <td><span class="label label-default">{{ $dispute->replies_count }}</span></td>
          <td>{{ $dispute->updated_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            <a href="{{ route('admin.support.dispute.show', $dispute->id) }}" class="admin-action-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
            @can('response', $dispute)
              <a href="javascript:void(0)" data-link="{{ route('admin.support.dispute.response', $dispute) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.response') }}" data-toggle="tooltip"><i class="fa fa-reply"></i></a>
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.trash_start', ['title' => trans('app.closed_tickets')])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.ticket') }}</th>
        <th>{{ trans('app.customer') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.response') }}</th>
        <th>{{ trans('app.updated_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($closed as $dispute)
        <tr>
          <td>
            <strong>{{ $dispute->ticketRef() }}</strong>
            {!! $dispute->statusName() !!}
          </td>
          <td>
            <div class="admin-table__shop-cell">
              <img src="{{ get_avatar_src($dispute->customer, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt="">
              <div>
                <strong>{{ $dispute->customer->getName() }}</strong>
                @if (Auth::user()->isFromPlatform() && $dispute->shop)
                  <br><span class="text-muted">{{ trans('app.vendor') . ': ' . optional($dispute->shop)->name }}</span>
                @endif
              </div>
            </div>
          </td>
          <td>
            <a href="{{ route('admin.support.dispute.show', $dispute->id) }}">{{ optional($dispute->dispute_type)->detail }}</a>
          </td>
          <td><span class="label label-default">{{ $dispute->replies_count }}</span></td>
          <td>{{ $dispute->updated_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            <a href="{{ route('admin.support.dispute.show', $dispute->id) }}" class="admin-action-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
