@extends('merchant.layouts.app')

@section('page_title', trans('nav.disputes') ?? 'Dispute Tickets')

@section('content')
  <div class="mp-panel" style="margin-bottom:16px;">
    <div class="mp-panel__head" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
      <h2 style="margin:0;font-size:16px;">{{ trans('nav.disputes') ?? 'Dispute Tickets' }}</h2>
      <a href="{{ route('merchant.support.dispute.create') }}" class="btn btn-sm btn-primary">
        <i class="fa fa-plus"></i> Raise Ticket
      </a>
    </div>
    <div class="mp-panel__body" style="padding:0;">
      <div style="overflow-x:auto;">
        <table class="table table-hover" style="margin:0;">
          <thead>
            <tr>
              <th>Ticket</th>
              <th>{{ trans('app.order') ?? 'Order' }}</th>
              <th>{{ trans('app.customer') ?? 'Customer' }}</th>
              <th>{{ trans('app.type') ?? 'Type' }}</th>
              <th>{{ trans('app.status') ?? 'Status' }}</th>
              <th>Raised by</th>
              <th>{{ trans('app.updated_at') ?? 'Updated' }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($open as $dispute)
              <tr>
                <td><strong>{{ $dispute->ticketRef() }}</strong></td>
                <td>{{ optional($dispute->order)->order_number }}</td>
                <td>{{ optional($dispute->customer)->getName() }}</td>
                <td>{{ optional($dispute->dispute_type)->detail }}</td>
                <td>{!! $dispute->statusName() !!}</td>
                <td>{{ $dispute->raisedByLabel() }}</td>
                <td>{{ $dispute->updated_at->diffForHumans() }}</td>
                <td>
                  <a href="{{ route('merchant.support.dispute.show', $dispute) }}" class="btn btn-xs btn-default">
                    View
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" style="padding:24px;text-align:center;color:#888;">No open dispute tickets</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="mp-panel">
    <div class="mp-panel__head"><h2 style="margin:0;font-size:16px;">Closed Tickets</h2></div>
    <div class="mp-panel__body" style="padding:0;">
      <div style="overflow-x:auto;">
        <table class="table table-hover" style="margin:0;">
          <thead>
            <tr>
              <th>Ticket</th>
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
                <td>{{ optional($dispute->order)->order_number }}</td>
                <td>{{ optional($dispute->customer)->getName() }}</td>
                <td>{{ optional($dispute->dispute_type)->detail }}</td>
                <td>{!! $dispute->statusName() !!}</td>
                <td>{{ $dispute->updated_at->diffForHumans() }}</td>
                <td>
                  <a href="{{ route('merchant.support.dispute.show', $dispute) }}" class="btn btn-xs btn-default">View</a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" style="padding:24px;text-align:center;color:#888;">No closed tickets</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
