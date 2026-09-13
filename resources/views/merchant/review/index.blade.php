@extends('merchant.layouts.app')

@section('page_title', trans('app.reviews'))

@section('content')
  <div class="mp-panel" style="margin-bottom:16px;">
    <div class="mp-panel__head">
      <h2 style="margin:0;font-size:16px;">{{ trans('app.product_reviews') }}</h2>
    </div>
    <div class="mp-panel__body" style="padding:0;">
      <div style="overflow-x:auto;">
        <table class="table table-hover" style="margin:0;">
          <thead>
            <tr>
              <th>{{ trans('app.product') }}</th>
              <th>{{ trans('app.customer') }}</th>
              <th>{{ trans('app.rating') }}</th>
              <th>{{ trans('app.comment') }}</th>
              <th>{{ trans('app.reply') }}</th>
              <th>{{ trans('app.created_at') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($product as $review)
              <tr>
                <td>{{ optional($review->reviewable)->title }}</td>
                <td>{{ optional($review->customer)->getName() }}</td>
                <td>{{ $review->rating }} / 5</td>
                <td>{{ Str::limit($review->comment, 60) }}</td>
                <td>
                  @if ($review->hasReply())
                    <span class="label label-success">{{ trans('app.replied') }}</span>
                  @else
                    <span class="label label-default">{{ trans('app.awaiting_reply') }}</span>
                  @endif
                </td>
                <td>{{ $review->created_at->diffForHumans() }}</td>
                <td>
                  <a href="{{ route('merchant.review.show', $review) }}" class="btn btn-xs btn-default">{{ trans('app.view') }}</a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" style="padding:24px;text-align:center;color:#888;">{{ trans('app.no_product_reviews') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="mp-panel">
    <div class="mp-panel__head">
      <h2 style="margin:0;font-size:16px;">{{ trans('app.store_reviews') }}</h2>
    </div>
    <div class="mp-panel__body" style="padding:0;">
      <div style="overflow-x:auto;">
        <table class="table table-hover" style="margin:0;">
          <thead>
            <tr>
              <th>{{ trans('app.customer') }}</th>
              <th>{{ trans('app.rating') }}</th>
              <th>{{ trans('app.comment') }}</th>
              <th>{{ trans('app.reply') }}</th>
              <th>{{ trans('app.created_at') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($store as $review)
              <tr>
                <td>{{ optional($review->customer)->getName() }}</td>
                <td>{{ $review->rating }} / 5</td>
                <td>{{ Str::limit($review->comment, 60) }}</td>
                <td>
                  @if ($review->hasReply())
                    <span class="label label-success">{{ trans('app.replied') }}</span>
                  @else
                    <span class="label label-default">{{ trans('app.awaiting_reply') }}</span>
                  @endif
                </td>
                <td>{{ $review->created_at->diffForHumans() }}</td>
                <td>
                  <a href="{{ route('merchant.review.show', $review) }}" class="btn btn-xs btn-default">{{ trans('app.view') }}</a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" style="padding:24px;text-align:center;color:#888;">{{ trans('app.no_store_reviews') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
