@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.delete_request') ?? 'Delete Request' }} #{{ $deleteRequest->id }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => (trans('app.delete_request') ?? 'Delete Request') . ' #' . $deleteRequest->id,
    'icon' => 'fa-trash',
  ])

  <div class="row mb-4">
    <div class="col-md-6">
      <p><strong>{{ trans('app.shop_name') }}:</strong> {{ optional($deleteRequest->shop)->name ?? trans('app.not_available') }}</p>
      <p><strong>{{ trans('app.requested_by') }}:</strong> {{ optional($deleteRequest->requester)->getName() ?? trans('app.not_available') }}</p>
      <p><strong>{{ trans('app.requested_at') }}:</strong> {{ $deleteRequest->created_at->toDayDateTimeString() }}</p>
    </div>
    <div class="col-md-6 text-right">
      @if ($deleteRequest->isPending())
        @can('approve', $deleteRequest)
          <form action="{{ route('admin.support.review.deleteRequests.approve', $deleteRequest) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success confirm">
              <i class="fa fa-check"></i> {{ trans('app.approve') }}
            </button>
          </form>
        @endcan
      @else
        @if ($deleteRequest->status === \App\Models\ReviewDeleteRequest::STATUS_APPROVED)
          <span class="label label-success">{{ trans('app.approved') ?? 'Approved' }}</span>
        @else
          <span class="label label-danger">{{ trans('app.rejected') ?? 'Rejected' }}</span>
        @endif
      @endif
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="box box-default">
        <div class="box-header with-border">
          <h3 class="box-title">{{ trans('app.review') ?? 'Review' }}</h3>
        </div>
        <div class="box-body">
          @if ($deleteRequest->review)
            <p>
              <strong>{{ trans('app.product') }} / {{ trans('app.shop_name') }}:</strong>
              @php $reviewable = $deleteRequest->review->reviewable; @endphp
              @if ($deleteRequest->review->reviewable_type === \App\Models\Shop::class && $reviewable)
                {{ $reviewable->name }}
              @elseif ($deleteRequest->review->reviewable_type === \App\Models\Inventory::class && $reviewable)
                {{ $reviewable->title }}
              @else
                {{ trans('app.not_available') }}
              @endif
            </p>
            <p><strong>{{ trans('app.customer') }}:</strong> {{ optional($deleteRequest->review->customer)->getName() ?? trans('app.not_available') }}</p>
            <p>
              <strong>{{ trans('app.rating') ?? 'Rating' }}:</strong>
              <span class="text-warning">
                @for ($i = 1; $i <= 5; $i++)
                  <i class="fa {{ $i <= $deleteRequest->review->rating ? 'fa-star' : 'fa-star-o' }}"></i>
                @endfor
              </span>
            </p>
            <p><strong>{{ trans('app.comment') ?? 'Comment' }}:</strong> {{ $deleteRequest->review->comment ?: trans('app.not_available') }}</p>
            <p>
              @can('view', $deleteRequest->review)
                <a href="{{ route('admin.support.review.show', $deleteRequest->review) }}" class="btn btn-sm btn-default">
                  <i class="fa fa-eye"></i> {{ trans('app.view') }} {{ trans('app.review') ?? 'Review' }}
                </a>
              @endcan
            </p>
          @else
            <p class="text-muted">{{ trans('app.not_available') }}</p>
          @endif
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">{{ trans('app.reason') ?? 'Reason' }}</h3>
        </div>
        <div class="box-body">
          <p>{{ $deleteRequest->reason }}</p>
        </div>
      </div>
    </div>
  </div>

  @if ($deleteRequest->isPending())
    @can('reject', $deleteRequest)
      <div class="box box-danger mt-3">
        <div class="box-header with-border">
          <h3 class="box-title">{{ trans('app.reject') }}</h3>
        </div>
        <div class="box-body">
          {!! Form::open(['route' => ['admin.support.review.deleteRequests.reject', $deleteRequest], 'method' => 'POST']) !!}
            <div class="form-group">
              {!! Form::label('rejection_reason', trans('app.rejection_reason') . '*') !!}
              {!! Form::textarea('rejection_reason', null, ['class' => 'form-control', 'rows' => 3, 'required', 'maxlength' => 1000]) !!}
            </div>
            <button type="submit" class="btn btn-danger confirm">
              <i class="fa fa-times"></i> {{ trans('app.reject') }}
            </button>
          {!! Form::close() !!}
        </div>
      </div>
    @endcan
  @elseif ($deleteRequest->status === \App\Models\ReviewDeleteRequest::STATUS_REJECTED && $deleteRequest->rejection_reason)
    <div class="alert alert-warning mt-3">
      <strong>{{ trans('app.rejection_reason') }}:</strong> {{ $deleteRequest->rejection_reason }}
    </div>
  @endif

  @if (! $deleteRequest->isPending())
    <p class="text-muted mt-3">
      {{ trans('app.reviewed_by') ?? 'Reviewed by' }} {{ optional($deleteRequest->reviewer)->getName() ?? trans('app.not_available') }}
      @if ($deleteRequest->reviewed_at)
        &middot; {{ $deleteRequest->reviewed_at->diffForHumans() }}
      @endif
    </p>
  @endif

  <div class="mt-3">
    <a href="{{ route('admin.support.review.deleteRequests') }}" class="btn btn-default">
      <i class="fa fa-arrow-left"></i> {{ trans('app.back') }}
    </a>
  </div>

  @include('admin.partials.ui.card_end')
@endsection
