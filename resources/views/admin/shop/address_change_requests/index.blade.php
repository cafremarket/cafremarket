@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.address_change_requests') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.address_change_requests'),
    'icon' => 'fa-map-marker',
  ])

  @if ($requests->isEmpty())
    <p class="text-muted">{{ trans('app.no_address_change_requests') }}</p>
  @else
    <table class="table table-hover admin-table">
      <thead>
        <tr>
          <th>{{ trans('app.shop_name') }}</th>
          <th>{{ trans('app.owner') }}</th>
          <th>{{ trans('app.requested_by') }}</th>
          <th>{{ trans('app.requested_at') }}</th>
          <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($requests as $changeRequest)
          <tr>
            <td>
              <div class="admin-table__cell-with-thumb">
                <img src="{{ get_storage_file_url(optional($changeRequest->shop->logo)->path, 'tiny') }}" class="img-circle img-sm" alt="">
                {{ $changeRequest->shop->name }}
              </div>
            </td>
            <td>{{ optional($changeRequest->shop->owner)->getName() }}</td>
            <td>{{ optional($changeRequest->requester)->getName() ?? trans('app.not_available') }}</td>
            <td>{{ $changeRequest->created_at->diffForHumans() }}</td>
            <td class="row-options admin-row-actions">
              @can('update', $changeRequest->shop)
                <a href="{{ route('admin.vendor.shop.addressChangeRequests.show', $changeRequest) }}" class="btn btn-sm btn-default">
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
