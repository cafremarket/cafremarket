@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.reviews') ?? 'Reviews' }}
@endsection

@section('content')
  @php
    $reviewsIndexActions = '';
    if (Gate::allows('index', \App\Models\ReviewDeleteRequest::class)) {
      $reviewsIndexActions = '<a href="' . route('admin.support.review.deleteRequests') . '" class="btn btn-default btn-flat btn-sm"><i class="fa fa-trash"></i> ' . e(trans('app.pending_delete_requests') ?? 'Pending Delete Requests') . '</a>';
    }
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.reviews') ?? 'Reviews',
    'icon' => 'fa-star',
    'actions' => $reviewsIndexActions,
  ])

  <form method="GET" action="{{ route('admin.support.review.index') }}" class="form-inline mb-3">
    <div class="form-group">
      {!! Form::label('type', trans('app.type')) !!}
      {!! Form::select('type', [
          '' => trans('app.all'),
          \App\Models\Review::TYPE_PRODUCT => trans('app.product'),
          \App\Models\Review::TYPE_STORE => trans('app.store') ?? 'Store',
      ], request('type'), ['class' => 'form-control']) !!}
    </div>

    <div class="form-group" style="margin-left: 10px;">
      {!! Form::label('rating', trans('app.rating') ?? 'Rating') !!}
      {!! Form::select('rating', [
          '' => trans('app.all'),
          1 => '1',
          2 => '2',
          3 => '3',
          4 => '4',
          5 => '5',
      ], request('rating'), ['class' => 'form-control']) !!}
    </div>

    <button type="submit" class="btn btn-default" style="margin-left: 10px;">
      <i class="fa fa-filter"></i> {{ trans('app.filter') }}
    </button>
  </form>

  @if ($reviews->isEmpty())
    <p class="text-muted">{{ trans('app.no_reviews') ?? 'No reviews found.' }}</p>
  @else
    <table class="table table-hover admin-table">
      <thead>
        <tr>
          <th>{{ trans('app.type') }}</th>
          <th>{{ trans('app.product') }} / {{ trans('app.shop_name') }}</th>
          <th>{{ trans('app.customer') }}</th>
          <th>{{ trans('app.rating') ?? 'Rating' }}</th>
          <th>{{ trans('app.comment') ?? 'Comment' }}</th>
          <th>{{ trans('app.reply') }}</th>
          <th>{{ trans('app.created_at') }}</th>
          <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($reviews as $review)
          <tr>
            <td>
              @if ($review->type === \App\Models\Review::TYPE_PRODUCT)
                <span class="label label-info">{{ trans('app.product') }}</span>
              @else
                <span class="label label-primary">{{ trans('app.store') ?? 'Store' }}</span>
              @endif
            </td>
            <td>
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
            </td>
            <td>{{ optional($review->customer)->getName() ?? trans('app.not_available') }}</td>
            <td>
              <span class="text-warning">
                @for ($i = 1; $i <= 5; $i++)
                  <i class="fa {{ $i <= $review->rating ? 'fa-star' : 'fa-star-o' }}"></i>
                @endfor
              </span>
            </td>
            <td>{{ Str::limit($review->comment, 60) }}</td>
            <td>
              @if ($review->hasReply())
                <span class="label label-success">{{ trans('app.replied') ?? 'Replied' }}</span>
              @else
                <span class="label label-default">{{ trans('app.no_reply') ?? 'No reply' }}</span>
              @endif
            </td>
            <td>{{ $review->created_at->diffForHumans() }}</td>
            <td class="row-options admin-row-actions">
              @can('view', $review)
                <a href="{{ route('admin.support.review.show', $review) }}" class="btn btn-sm btn-default">
                  <i class="fa fa-eye"></i> {{ trans('app.view') }}
                </a>
              @endcan
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    {{ $reviews->links() }}
  @endif

  @include('admin.partials.ui.card_end')
@endsection
