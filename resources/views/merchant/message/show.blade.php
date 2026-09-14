@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.message') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.message'),
    'icon' => 'fa-envelope-open',
    'class' => 'admin-card--flush',
    'bodyClass' => 'admin-card__body--flush-top admin-mailbox-read',
  ])

      <div class="admin-mailbox-read__header">
        <div class="admin-mailbox-read__meta">
          <h3 class="admin-mailbox-read__subject">{!! $message->subject !!}</h3>
          <div class="admin-mailbox-read__from">
            {{ $message->user_id ? trans('app.to') : trans('app.from') }}:
            <strong>{{ $message->customer->getName() }}</strong>
            @if ($message->phone)
              <span class="admin-mailbox-read__phone"><i class="fa fa-phone"></i> {{ $message->phone }}</span>
            @endif
            <span class="admin-mailbox-read__date">{{ $message->updated_at->toDayDateTimeString() }}</span>
          </div>
          @if ($message->order)
            <div class="admin-mailbox-read__order">
              {{ trans('app.order') }}:
              <a href="{{ panel_route('admin.order.order.show', $message->order->id) }}"><strong>{{ $message->order->order_number }}</strong></a>
            </div>
          @endif
        </div>
      </div>

      <div class="admin-mailbox__toolbar no-print">
        @if ($message->label < \App\Models\Message::LABEL_DRAFT)
          <a href="javascript:void(0)" data-link="{{ panel_route('admin.support.message.reply', $message) }}" class="ajax-modal-btn btn btn-default btn-sm">
            <i class="fa fa-reply"></i> {{ trans('app.reply') }}
          </a>
        @endif
      </div>

      <div class="admin-mailbox-read__body">
        {!! $message->message !!}
      </div>
  @include('admin.partials.ui.card_body_end')

    @if ($message->attachments->count())
      <div class="admin-card__footer">
        @include('admin.message._view_attachments')
      </div>
    @endif

    @unless ($message->label == \App\Models\Message::LABEL_DRAFT)
      @if ($message->replies->count())
        <div class="admin-card__footer admin-mailbox-read__replies">
          <strong class="admin-mailbox-read__replies-title">{{ trans('app.replies') }}</strong>
          @foreach ($message->replies as $reply)
            <div class="row">
              <div class="col-md-2 nopadding-right no-print">
                @if ($reply->user_id)
                  <img src="{{ get_avatar_src($reply->user, 'tiny') }}" class="img-circle img-sm" alt="{{ trans('app.avatar') }}">
                  <span class="small">{{ $reply->user->getName() }}</span>
                @endif
              </div>
              <div class="col-md-8 nopadding">
                <blockquote style="font-size: 1em;" class="{{ $reply->customer_id ? 'blockquote-reverse' : '' }}">
                  {!! $reply->reply !!}
                  @if (count($reply->attachments))
                    <small class="no-print">
                      {{ trans('app.attachments') . ': ' }}
                      @foreach ($reply->attachments as $attachment)
                        <a href="{{ route('attachment.download', $attachment) }}"><i class="fa fa-file"></i></a>
                      @endforeach
                    </small>
                  @endif
                  <footer>{{ $reply->updated_at->diffForHumans() }}</footer>
                </blockquote>
              </div>
              <div class="col-md-2 nopadding-left no-print">
                @if ($reply->customer_id)
                  <img src="{{ get_avatar_src($reply->customer, 'tiny') }}" class="img-circle img-sm" alt="{{ trans('app.avatar') }}">
                  <span class="small">{{ $reply->customer->getName() }}</span>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      @endif
    @endunless

    @if ($message->label < \App\Models\Message::LABEL_DRAFT)
      <div class="admin-card__footer admin-mailbox__toolbar admin-mailbox__toolbar--bottom no-print">
        <a href="javascript:void(0)" data-link="{{ panel_route('admin.support.message.reply', $message) }}" class="ajax-modal-btn btn btn-default btn-sm">
          <i class="fa fa-reply"></i> {{ trans('app.reply') }}
        </a>
      </div>
    @endif
@endsection
