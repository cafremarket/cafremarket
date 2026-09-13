@extends('merchant.layouts.app')

@php
  $reviewableName = $review->type === \App\Models\Review::TYPE_STORE
      ? optional($review->reviewable)->name
      : optional($review->reviewable)->title;
  $pendingDeleteRequest = $review->deleteRequests->firstWhere('status', \App\Models\ReviewDeleteRequest::STATUS_PENDING);
  $reviewTitle = $review->type === \App\Models\Review::TYPE_STORE
      ? trans('app.store_review')
      : trans('app.product_review');
@endphp

@section('page_title', $reviewTitle.($reviewableName ? ' - '.$reviewableName : ''))

@section('content')
  <div class="mp-panel" style="margin-bottom:16px;">
    <div class="mp-panel__head" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;">
      <div>
        <h2 style="margin:0;font-size:16px;">
          {{ $reviewTitle }}
          @if ($reviewableName)
            &mdash; {{ $reviewableName }}
          @endif
        </h2>
      </div>
      <a href="{{ route('merchant.review.index') }}" class="btn btn-default btn-sm">{{ trans('app.back_to_reviews') }}</a>
    </div>
    <div class="mp-panel__body">
      @if ($review->hasPendingDeleteRequest())
        <div class="alert alert-warning">{{ trans('app.deletion_requested_waiting') }}</div>
      @endif

      <dl style="display:grid;grid-template-columns:140px 1fr;gap:8px 12px;margin:0;">
        <dt>{{ trans('app.customer') }}</dt>
        <dd>{{ optional($review->customer)->getName() }}</dd>
        <dt>{{ trans('app.rating') }}</dt>
        <dd>{{ $review->rating }} / 5</dd>
        <dt>{{ trans('app.order') }}</dt>
        <dd>
          @if ($review->order)
            <a href="{{ url('merchant/order/order/'.$review->order->id) }}">#{{ $review->order->order_number }}</a>
          @else
            &mdash;
          @endif
        </dd>
        <dt>{{ trans('app.created_at') }}</dt>
        <dd>{{ $review->created_at }}</dd>
      </dl>

      @if ($review->comment)
        <div style="margin-top:16px;padding:12px;background:#f7f7f8;border-radius:8px;">
          <strong>{{ trans('app.comment') }}</strong>
          <div>{!! nl2br(e($review->comment)) !!}</div>
        </div>
      @endif
    </div>
  </div>

  <div class="mp-panel" style="margin-bottom:16px;">
    <div class="mp-panel__head"><h2 style="margin:0;font-size:16px;">{{ trans('app.reply') }}</h2></div>
    <div class="mp-panel__body">
      @if ($review->hasReply())
        <div style="padding:12px;background:#f7f7f8;border-radius:8px;margin-bottom:16px;">
          <div>{!! nl2br(e($review->reply)) !!}</div>
          @if ($review->replied_at)
            <div style="margin-top:6px;color:#888;font-size:12px;">{{ trans('app.posted') }} {{ $review->replied_at->diffForHumans() }}</div>
          @endif
        </div>
      @endif

      {!! Form::open(['route' => ['merchant.review.reply', $review]]) !!}
      <div class="form-group">
        {!! Form::label('reply', $review->hasReply() ? trans('app.update_reply') : trans('app.write_a_reply')) !!}
        {!! Form::textarea('reply', old('reply', $review->reply), ['class' => 'form-control', 'rows' => 4, 'maxlength' => 1000, 'required', 'placeholder' => trans('app.write_a_reply_placeholder')]) !!}
      </div>
      <button type="submit" class="btn btn-primary btn-sm">{{ $review->hasReply() ? trans('app.update_reply') : trans('app.post_reply') }}</button>
      {!! Form::close() !!}
    </div>
  </div>

  <div class="mp-panel">
    <div class="mp-panel__head"><h2 style="margin:0;font-size:16px;">{{ trans('app.deletion_request') }}</h2></div>
    <div class="mp-panel__body">
      @if ($review->hasPendingDeleteRequest())
        <div class="alert alert-warning">
          {{ trans('app.deletion_requested_waiting') }}
          @if ($pendingDeleteRequest)
            <div style="margin-top:6px;">{!! nl2br(e($pendingDeleteRequest->reason)) !!}</div>
          @endif
        </div>
      @else
        <p style="color:#888;">{{ trans('app.only_admin_can_delete_review') }}</p>
        {!! Form::open(['route' => ['merchant.review.requestDelete', $review]]) !!}
        <div class="form-group">
          {!! Form::label('reason', trans('app.reason').' *') !!}
          {!! Form::textarea('reason', null, ['class' => 'form-control', 'rows' => 3, 'maxlength' => 1000, 'required', 'placeholder' => trans('app.deletion_reason_placeholder')]) !!}
        </div>
        <button type="submit" class="btn btn-warning btn-sm">{{ trans('app.request_deletion') }}</button>
        {!! Form::close() !!}
      @endif

      @if ($review->deleteRequests->count())
        <div style="margin-top:20px;">
          <strong>{{ trans('app.past_requests') }}</strong>
          @foreach ($review->deleteRequests as $deleteRequest)
            <div style="padding:12px 0;border-bottom:1px solid #eee;">
              <div style="display:flex;justify-content:space-between;gap:8px;margin-bottom:6px;">
                @if ($deleteRequest->status === \App\Models\ReviewDeleteRequest::STATUS_APPROVED)
                  <span class="label label-success">{{ trans('app.approved') }}</span>
                @elseif ($deleteRequest->status === \App\Models\ReviewDeleteRequest::STATUS_REJECTED)
                  <span class="label label-danger">{{ trans('app.rejected') }}</span>
                @else
                  <span class="label label-default">{{ trans('app.pending') }}</span>
                @endif
                <span style="color:#888;font-size:12px;">{{ $deleteRequest->created_at->toDayDateTimeString() }}</span>
              </div>
              <div>{!! nl2br(e($deleteRequest->reason)) !!}</div>
              @if ($deleteRequest->status === \App\Models\ReviewDeleteRequest::STATUS_REJECTED && $deleteRequest->rejection_reason)
                <div style="margin-top:6px;color:#a94442;"><strong>{{ trans('app.rejection_reason') }}:</strong> {!! nl2br(e($deleteRequest->rejection_reason)) !!}</div>
              @endif
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>
@endsection
