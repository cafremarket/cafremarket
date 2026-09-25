@extends('merchant.layouts.app')

@section('page_title', trans('nav.disputes') ?? 'Dispute Tickets')

@section('content')
  <div class="mp-panel">
    <div class="mp-panel__head">
      <div class="mp-panel__head-text">
        <h2>{{ trans('app.open_tickets') }}</h2>
        <p>{{ trans('app.open_tickets_help') }}</p>
      </div>
      <a href="{{ route('merchant.support.dispute.create') }}" class="mp-btn mp-btn--primary mp-btn--sm">
        <i class="fa fa-plus"></i> {{ trans('app.raise_ticket') }}
      </a>
    </div>
    <div class="mp-panel__body mp-panel__body--flush">
      <table class="mp-table">
        <thead>
          <tr>
            <th>{{ trans('app.model.ticket') }}</th>
            <th>{{ trans('app.order') ?? 'Order' }}</th>
            <th>{{ trans('app.customer') ?? 'Customer' }}</th>
            <th>{{ trans('app.type') ?? 'Type' }}</th>
            <th>{{ trans('app.status') ?? 'Status' }}</th>
            <th>{{ trans('app.raised_by') }}</th>
            <th>{{ trans('app.updated_at') ?? 'Updated' }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($open as $dispute)
            <tr>
              <td><strong>{{ $dispute->ticketRef() }}</strong></td>
              <td>{{ optional($dispute->order)->order_number ?: '—' }}</td>
              <td>{{ optional($dispute->customer)->getName() ?: '—' }}</td>
              <td>{{ optional($dispute->dispute_type)->detail ?: '—' }}</td>
              <td>{!! $dispute->statusName() !!}</td>
              <td>{{ $dispute->raisedByLabel() }}</td>
              <td>{{ $dispute->updated_at->diffForHumans() }}</td>
              <td>
                <a href="{{ route('merchant.support.dispute.show', $dispute) }}" class="mp-btn mp-btn--outline mp-btn--sm">{{ trans('app.view') }}</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="mp-table__empty">{{ trans('app.no_open_dispute_tickets') }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mp-panel">
    <div class="mp-panel__head">
      <div class="mp-panel__head-text">
        <h2>{{ trans('app.closed_tickets') }}</h2>
        <p>{{ trans('app.closed_tickets_help') }}</p>
      </div>
    </div>
    <div class="mp-panel__body mp-panel__body--flush">
      <table class="mp-table">
        <thead>
          <tr>
            <th>{{ trans('app.model.ticket') }}</th>
            <th>{{ trans('app.order') ?? 'Order' }}</th>
            <th>{{ trans('app.customer') ?? 'Customer' }}</th>
            <th>{{ trans('app.type') ?? 'Type' }}</th>
            <th>{{ trans('app.status') ?? 'Status' }}</th>
            <th>{{ trans('app.updated_at') ?? 'Updated' }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($closed as $dispute)
            <tr>
              <td><strong>{{ $dispute->ticketRef() }}</strong></td>
              <td>{{ optional($dispute->order)->order_number ?: '—' }}</td>
              <td>{{ optional($dispute->customer)->getName() ?: '—' }}</td>
              <td>{{ optional($dispute->dispute_type)->detail ?: '—' }}</td>
              <td>{!! $dispute->statusName() !!}</td>
              <td>{{ $dispute->updated_at->diffForHumans() }}</td>
              <td>
                <a href="{{ route('merchant.support.dispute.show', $dispute) }}" class="mp-btn mp-btn--outline mp-btn--sm">{{ trans('app.view') }}</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="mp-table__empty">{{ trans('app.no_closed_tickets') }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
