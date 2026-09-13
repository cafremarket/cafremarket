@extends('merchant.layouts.app')

@section('page_title', trans('app.dispute_ticket').' '.$dispute->ticketRef())

@section('content')
  <div class="mp-panel">
    <div class="mp-panel__head">
      <div class="mp-panel__head-text">
        <h2>{{ trans('app.ticket') }} {{ $dispute->ticketRef() }}</h2>
        <p>
          {!! $dispute->statusName() !!}
          <span class="label label-default">{{ trans('app.raised_by') }} {{ $dispute->raisedByLabel() }}</span>
        </p>
      </div>
      <a href="{{ route('merchant.support.dispute.index') }}" class="mp-btn mp-btn--outline mp-btn--sm">{{ trans('app.back_to_tickets') }}</a>
    </div>
    <div class="mp-panel__body">
      @if ($dispute->isCloseRequested())
        <div class="mp-alert mp-alert--warning">{{ trans('app.close_requested_waiting_admin') }}</div>
      @elseif ($dispute->isResolved())
        <div class="mp-alert mp-alert--success">{{ trans('app.ticket_resolved_request_close') }}</div>
      @elseif ($dispute->isClosed())
        <div class="mp-alert mp-alert--info">{{ trans('app.ticket_closed_admin_only') }}</div>
      @endif

      <dl class="mp-dl">
        <dt>{{ trans('app.order') }}</dt>
        <dd>
          @if ($dispute->order)
            <a href="{{ url('merchant/order/order/'.$dispute->order->id) }}">#{{ $dispute->order->order_number }}</a>
          @else
            —
          @endif
        </dd>
        <dt>{{ trans('app.customer') }}</dt>
        <dd>{{ optional($dispute->customer)->getName() ?: '—' }}</dd>
        <dt>{{ trans('app.type') }}</dt>
        <dd>{{ optional($dispute->dispute_type)->detail ?: '—' }}</dd>
        <dt>{{ trans('app.refund_amount') }}</dt>
        <dd>{{ get_formated_currency($dispute->refund_amount, 2, optional($dispute->order)->currency_id) }}</dd>
        <dt>{{ trans('app.created_at') }}</dt>
        <dd>{{ $dispute->created_at }}</dd>
      </dl>

      @if ($dispute->description)
        <div class="mp-note">
          <strong>{{ trans('app.issue') }}</strong>
          <div>{!! nl2br(e($dispute->description)) !!}</div>
        </div>
      @endif

      @if ($dispute->attachments->count())
        <div class="mp-actions">
          <strong>{{ trans('app.attachments') }}:</strong>
          @foreach ($dispute->attachments as $attachment)
            <a href="{{ route('attachment.download', $attachment) }}" class="mp-btn mp-btn--outline mp-btn--sm"><i class="fa fa-file"></i> {{ trans('app.file') }}</a>
          @endforeach
        </div>
      @endif

      @if ($dispute->isOpen())
        <div class="mp-actions">
          @if ($dispute->canMarkResolved())
            {!! Form::open(['route' => ['merchant.support.dispute.resolved', $dispute]]) !!}
            <button type="submit" class="mp-btn mp-btn--success mp-btn--sm">{{ trans('app.mark_as_resolved') }}</button>
            {!! Form::close() !!}
          @endif
          @if ($dispute->canRequestClose())
            {!! Form::open(['route' => ['merchant.support.dispute.requestClose', $dispute]]) !!}
            <button type="submit" class="mp-btn mp-btn--warning mp-btn--sm">{{ trans('app.request_close_dispute') }}</button>
            {!! Form::close() !!}
          @endif
        </div>
      @endif
    </div>
  </div>

  <div class="mp-panel">
    <div class="mp-panel__head">
      <div class="mp-panel__head-text">
        <h2>{{ trans('app.ticket_updates') }}</h2>
      </div>
    </div>
    <div class="mp-panel__body">
      <div class="mp-thread">
        @forelse ($dispute->replies->sortBy('created_at') as $reply)
          <div class="mp-thread__item">
            <div class="mp-thread__meta">
              <strong>
                @if ($reply->customer_id)
                  {{ optional($reply->customer)->getName() ?? trans('app.customer') }}
                @elseif ($reply->user_id)
                  {{ optional($reply->user)->getName() ?? trans('app.staff') }}
                @else
                  {{ trans('app.support') }}
                @endif
              </strong>
              <span>{{ $reply->created_at->toDayDateTimeString() }}</span>
            </div>
            <div class="mp-thread__body">{!! nl2br(e($reply->reply)) !!}</div>
            @if ($reply->attachments && $reply->attachments->count())
              <div class="mp-actions">
                @foreach ($reply->attachments as $attachment)
                  <a href="{{ route('attachment.download', $attachment) }}" class="mp-btn mp-btn--outline mp-btn--sm"><i class="fa fa-paperclip"></i></a>
                @endforeach
              </div>
            @endif
          </div>
        @empty
          <p class="help-block" style="margin:0;">{{ trans('app.no_updates_yet') }}</p>
        @endforelse
      </div>
    </div>
  </div>

  @if ($dispute->canReply())
    <div class="mp-panel">
      <div class="mp-panel__head">
        <div class="mp-panel__head-text">
          <h2>{{ trans('app.add_response') }}</h2>
        </div>
      </div>
      <div class="mp-panel__body">
        {!! Form::open([
          'route' => ['merchant.support.dispute.response', $dispute],
          'files' => true,
        ]) !!}

        <div class="mp-form-group">
          {!! Form::label('status', trans('app.status')) !!}
          {!! Form::select('status', $statuses, $dispute->status, ['class' => 'mp-form-control']) !!}
          <p class="help-block">{{ trans('app.only_admin_can_set_closed') }}</p>
        </div>

        <div class="mp-form-group">
          {!! Form::label('reply', trans('app.ticket_updates').' *') !!}
          {!! Form::textarea('reply', null, ['class' => 'mp-form-control', 'rows' => 4, 'required', 'placeholder' => trans('app.write_ticket_update')]) !!}
        </div>

        <div class="mp-form-group">
          {!! Form::label('attachments', trans('app.attachments')) !!}
          {!! Form::file('attachments[]', ['multiple' => true, 'class' => 'mp-form-control']) !!}
        </div>

        <button type="submit" class="mp-btn mp-btn--primary">{{ trans('app.submit_update') }}</button>
        {!! Form::close() !!}
      </div>
    </div>
  @endif
@endsection
