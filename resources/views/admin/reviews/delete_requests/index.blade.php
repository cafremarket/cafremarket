@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.pending_delete_requests') ?? 'Pending Delete Requests' }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.pending_delete_requests') ?? 'Pending Delete Requests',
    'icon' => 'fa-trash',
  ])

  @if ($requests->isEmpty())
    <p class="text-muted">{{ trans('app.no_delete_requests') ?? 'No pending delete requests.' }}</p>
  @else
    <table class="table table-hover admin-table">
      <thead>
        <tr>
          <th>{{ trans('app.review') ?? 'Review' }}</th>
          <th>{{ trans('app.shop_name') }}</th>
          <th>{{ trans('app.requested_by') }}</th>
          <th>{{ trans('app.reason') ?? 'Reason' }}</th>
          <th>{{ trans('app.requested_at') }}</th>
          <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($requests as $request)
          <tr>
            <td>
              @can('view', $request->review)
                <a href="{{ route('admin.support.review.show', $request->review) }}">
                  {{ trans('app.review') ?? 'Review' }} #{{ $request->review_id }}
                </a>
              @else
                {{ trans('app.review') ?? 'Review' }} #{{ $request->review_id }}
              @endcan
            </td>
            <td>{{ optional($request->shop)->name ?? trans('app.not_available') }}</td>
            <td>{{ optional($request->requester)->getName() ?? trans('app.not_available') }}</td>
            <td>{{ Str::limit($request->reason, 60) }}</td>
            <td>{{ $request->created_at->diffForHumans() }}</td>
            <td class="row-options admin-row-actions">
              @can('view', $request)
                <a href="{{ route('admin.support.review.deleteRequests.show', $request) }}" class="btn btn-sm btn-default">
                  <i class="fa fa-eye"></i> {{ trans('app.view') }}
                </a>
              @endcan
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    {{ $requests->links() }}
  @endif

  @include('admin.partials.ui.card_end')
@endsection
