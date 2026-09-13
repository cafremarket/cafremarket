@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.review') ?? 'Review' }} #{{ $review->id }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => (trans('app.review') ?? 'Review') . ' #' . $review->id,
    'icon' => 'fa-star',
  ])

  <div class="row mb-4">
    <div class="col-md-6">
      <p>
        <strong>{{ trans('app.type') }}:</strong>
        @if ($review->type === \App\Models\Review::TYPE_PRODUCT)
          <span class="label label-info">{{ trans('app.product') }}</span>
        @else
          <span class="label label-primary">{{ trans('app.store') ?? 'Store' }}</span>
        @endif
      </p>
      <p>
        <strong>{{ trans('app.product') }} / {{ trans('app.shop_name') }}:</strong>
        @if ($review->reviewable_type === \App\Models\Shop::class && $review->reviewable)
          @can('view', $review->reviewable)
            <a href="{{ route('admin.vendor.shop.show', $review->reviewable_id) }}">{{ $review->reviewable->name }}</a>
          @else
            {{ $review->reviewable->name }}
          @endcan
        @elseif ($review->reviewable_type === \App\Models\Inventory::class && $review->reviewable)
          @can('view', $review->reviewable)
            <a href="{{ route('admin.stock.inventory.show', $review->reviewable_id) }}">{{ $review->reviewable->title }}</a>
          @else
            {{ $review->reviewable->title }}
          @endcan
        @else
          <span class="text-muted">{{ trans('app.not_available') }}</span>
        @endif
      </p>
      <p><strong>{{ trans('app.shop_name') }}:</strong> {{ optional($review->shop)->name ?? trans('app.not_available') }}</p>
      <p><strong>{{ trans('app.customer') }}:</strong> {{ optional($review->customer)->getName() ?? trans('app.not_available') }}</p>
      @if ($review->order)
        <p>
          <strong>{{ trans('app.order') }}:</strong>
          @can('view', $review->order)
            <a href="{{ route('admin.order.order.show', $review->order_id) }}">{{ $review->order->order_number ?? ('#' . $review->order_id) }}</a>
          @else
            {{ $review->order->order_number ?? ('#' . $review->order_id) }}
          @endcan
        </p>
      @endif
    </div>
    <div class="col-md-6">
      <p>
        <strong>{{ trans('app.rating') ?? 'Rating' }}:</strong>
        <span class="text-warning">
          @for ($i = 1; $i <= 5; $i++)
            <i class="fa {{ $i <= $review->rating ? 'fa-star' : 'fa-star-o' }}"></i>
          @endfor
        </span>
        ({{ $review->rating }}/5)
      </p>
      <p><strong>{{ trans('app.created_at') }}:</strong> {{ $review->created_at->toDayDateTimeString() }}</p>
    </div>
  </div>

  <div class="box box-default mb-3">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('app.comment') ?? 'Comment' }}</h3>
    </div>
    <div class="box-body">
      <p>{{ $review->comment ?: trans('app.not_available') }}</p>

      @if ($review->attachments->count())
        <div class="admin-detail-view__attachments">
          @foreach ($review->attachments as $attachment)
            <img src="{{ get_storage_file_url($attachment->path) }}" class="admin-detail-panel__thumb" alt="" style="max-width: 120px; margin-right: 10px;">
          @endforeach
        </div>
      @endif
    </div>
  </div>

  <div class="box box-default mb-3">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('app.reply') }}</h3>
    </div>
    <div class="box-body">
      @if ($review->hasReply())
        <p>{{ $review->reply }}</p>
        <p class="text-muted">
          {{ trans('app.replied_by') ?? 'Replied by' }} {{ optional($review->replier)->getName() ?? trans('app.not_available') }}
          @if ($review->replied_at)
            &middot; {{ $review->replied_at->diffForHumans() }}
          @endif
        </p>
      @else
        <p class="text-muted">{{ trans('app.no_reply_yet') ?? 'No reply yet.' }}</p>
      @endif
    </div>
  </div>

  @if ($review->deleteRequests->count())
    <div class="box box-default mb-3">
      <div class="box-header with-border">
        <h3 class="box-title">{{ trans('app.delete_requests') ?? 'Delete Requests' }}</h3>
      </div>
      <div class="box-body responsive-table">
        <table class="table table-hover admin-table admin-table--compact">
          <thead>
            <tr>
              <th>{{ trans('app.status') }}</th>
              <th>{{ trans('app.reason') ?? 'Reason' }}</th>
              <th>{{ trans('app.requested_by') }}</th>
              <th>{{ trans('app.rejection_reason') }}</th>
              <th>{{ trans('app.reviewed_by') ?? 'Reviewed by' }}</th>
              <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($review->deleteRequests as $deleteRequest)
              <tr>
                <td>
                  @if ($deleteRequest->status === \App\Models\ReviewDeleteRequest::STATUS_PENDING)
                    <span class="label label-warning">{{ trans('app.pending') ?? 'Pending' }}</span>
                  @elseif ($deleteRequest->status === \App\Models\ReviewDeleteRequest::STATUS_APPROVED)
                    <span class="label label-success">{{ trans('app.approved') ?? 'Approved' }}</span>
                  @else
                    <span class="label label-danger">{{ trans('app.rejected') ?? 'Rejected' }}</span>
                  @endif
                </td>
                <td>{{ Str::limit($deleteRequest->reason, 60) }}</td>
                <td>{{ optional($deleteRequest->requester)->getName() ?? trans('app.not_available') }}</td>
                <td>{{ $deleteRequest->rejection_reason ? Str::limit($deleteRequest->rejection_reason, 60) : '—' }}</td>
                <td>{{ optional($deleteRequest->reviewer)->getName() ?? '—' }}</td>
                <td class="row-options admin-row-actions">
                  @can('view', $deleteRequest)
                    <a href="{{ route('admin.support.review.deleteRequests.show', $deleteRequest) }}" class="btn btn-sm btn-default">
                      <i class="fa fa-eye"></i> {{ trans('app.view') }}
                    </a>
                  @endcan
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  @can('delete', $review)
    <div class="box box-danger mb-3">
      <div class="box-header with-border">
        <h3 class="box-title">{{ trans('app.danger_zone') ?? 'Danger Zone' }}</h3>
      </div>
      <div class="box-body">
        {!! Form::open(['route' => ['admin.support.review.destroy', $review], 'method' => 'DELETE']) !!}
          <button type="submit" class="btn btn-danger confirm">
            <i class="fa fa-trash"></i> {{ trans('app.delete') }}
          </button>
        {!! Form::close() !!}
      </div>
    </div>
  @endcan

  <div class="mt-3">
    <a href="{{ route('admin.support.review.index') }}" class="btn btn-default">
      <i class="fa fa-arrow-left"></i> {{ trans('app.back') }}
    </a>
  </div>

  @include('admin.partials.ui.card_end')
@endsection
