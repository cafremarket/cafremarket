@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.order_feedbacks') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.order_feedbacks'),
    'icon' => 'fa-star',
    'actions' => $feedbacks->total()
      ? '<span class="text-warning"><i class="fa fa-star"></i></span> ' . e(trans('app.average_rating')) . ': <strong>' . $average . '/5</strong> (' . $feedbacks->total() . ')'
      : '',
  ])

  <form method="GET" action="{{ panel_route('admin.order.feedbacks.index') }}" class="form-inline mb-3">
    <div class="form-group">
      {!! Form::text('q', request('q'), ['class' => 'form-control', 'placeholder' => trans('app.order_number')]) !!}
    </div>

    <div class="form-group" style="margin-left: 10px;">
      {!! Form::label('rating', trans('app.rating') ?? 'Rating') !!}
      {!! Form::select('rating', ['' => trans('app.all'), 5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1'], request('rating'), ['class' => 'form-control']) !!}
    </div>

    <button type="submit" class="btn btn-default" style="margin-left: 10px;">
      <i class="fa fa-filter"></i> {{ trans('app.filter') }}
    </button>
  </form>

  @if ($feedbacks->isEmpty())
    <p class="text-muted">{{ trans('app.no_order_feedbacks') }}</p>
  @else
    <table class="table table-hover admin-table">
      <thead>
        <tr>
          <th>{{ trans('app.order_number') }}</th>
          @if (Auth::user()->isFromPlatform())
            <th>{{ trans('app.shop_name') }}</th>
          @endif
          <th>{{ trans('app.customer') }}</th>
          <th>{{ trans('app.rating') ?? 'Rating' }}</th>
          <th>{{ trans('app.comment') ?? 'Comment' }}</th>
          <th>{{ trans('app.created_at') }}</th>
          <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($feedbacks as $feedback)
          <tr>
            <td>{{ optional($feedback->order)->order_number ?? trans('app.not_available') }}</td>
            @if (Auth::user()->isFromPlatform())
              <td>{{ $feedback->shop->name }}</td>
            @endif
            <td>{{ $feedback->customer->getName() }}</td>
            <td>
              <span class="text-warning" title="{{ $feedback->rating }}/5">
                @for ($i = 1; $i <= 5; $i++)
                  <i class="fa {{ $i <= $feedback->rating ? 'fa-star' : 'fa-star-o' }}"></i>
                @endfor
              </span>
            </td>
            <td style="max-width:360px; white-space:pre-line;">{{ $feedback->comment }}</td>
            <td>{{ $feedback->created_at->diffForHumans() }}</td>
            <td class="row-options admin-row-actions">
              @if ($feedback->order)
                <a href="{{ panel_route('admin.order.order.show', $feedback->order_id) }}" class="btn btn-sm btn-default">
                  <i class="fa fa-eye"></i> {{ trans('app.view') }}
                </a>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    {{ $feedbacks->links() }}
  @endif

  @include('admin.partials.ui.card_end')
@endsection
