@extends('merchant.layouts.app')

@section('page_title', 'Dispute Ticket '.$dispute->ticketRef())

@section('content')
  <div class="mp-panel" style="margin-bottom:16px;">
    <div class="mp-panel__head" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;">
      <div>
        <h2 style="margin:0;font-size:16px;">Ticket {{ $dispute->ticketRef() }}</h2>
        <div style="margin-top:6px;">
          {!! $dispute->statusName() !!}
          <span class="label label-default">Raised by {{ $dispute->raisedByLabel() }}</span>
        </div>
      </div>
      <a href="{{ route('merchant.support.dispute.index') }}" class="btn btn-default btn-sm">Back to tickets</a>
    </div>
    <div class="mp-panel__body">
      <dl style="display:grid;grid-template-columns:140px 1fr;gap:8px 12px;margin:0;">
        <dt>{{ trans('app.order') ?? 'Order' }}</dt>
        <dd>
          @if ($dispute->order)
            <a href="{{ url('merchant/order/order/'.$dispute->order->id) }}">#{{ $dispute->order->order_number }}</a>
          @else
            —
          @endif
        </dd>
        <dt>{{ trans('app.customer') ?? 'Customer' }}</dt>
        <dd>{{ optional($dispute->customer)->getName() }}</dd>
        <dt>{{ trans('app.type') ?? 'Type' }}</dt>
        <dd>{{ optional($dispute->dispute_type)->detail }}</dd>
        <dt>{{ trans('app.refund_amount') ?? 'Refund' }}</dt>
        <dd>{{ get_formated_currency($dispute->refund_amount, 2, optional($dispute->order)->currency_id) }}</dd>
        <dt>{{ trans('app.created_at') ?? 'Created' }}</dt>
        <dd>{{ $dispute->created_at }}</dd>
      </dl>

      @if ($dispute->description)
        <div style="margin-top:16px;padding:12px;background:#f7f7f8;border-radius:8px;">
          <strong>Issue</strong>
          <div>{!! nl2br(e($dispute->description)) !!}</div>
        </div>
      @endif

      @if ($dispute->attachments->count())
        <div style="margin-top:12px;">
          <strong>{{ trans('app.attachments') ?? 'Attachments' }}:</strong>
          @foreach ($dispute->attachments as $attachment)
            <a href="{{ route('attachment.download', $attachment) }}" class="btn btn-xs btn-default"><i class="fa fa-file"></i></a>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  <div class="mp-panel" style="margin-bottom:16px;">
    <div class="mp-panel__head"><h2 style="margin:0;font-size:16px;">Ticket updates</h2></div>
    <div class="mp-panel__body">
      @forelse ($dispute->replies->sortBy('created_at') as $reply)
        <div style="padding:12px 0;border-bottom:1px solid #eee;">
          <div style="display:flex;justify-content:space-between;gap:8px;margin-bottom:6px;">
            <strong>
              @if ($reply->customer_id)
                {{ optional($reply->customer)->getName() ?? 'Customer' }}
              @elseif ($reply->user_id)
                {{ optional($reply->user)->getName() ?? 'Staff' }}
              @else
                Support
              @endif
            </strong>
            <span style="color:#888;font-size:12px;">{{ $reply->created_at->toDayDateTimeString() }}</span>
          </div>
          <div>{!! nl2br(e($reply->reply)) !!}</div>
          @if ($reply->attachments && $reply->attachments->count())
            <div style="margin-top:8px;">
              @foreach ($reply->attachments as $attachment)
                <a href="{{ route('attachment.download', $attachment) }}" class="btn btn-xs btn-default"><i class="fa fa-paperclip"></i></a>
              @endforeach
            </div>
          @endif
        </div>
      @empty
        <p style="color:#888;margin:0;">No updates yet. Add a response below.</p>
      @endforelse
    </div>
  </div>

  @if ($dispute->isOpen())
    <div class="mp-panel">
      <div class="mp-panel__head"><h2 style="margin:0;font-size:16px;">Add response</h2></div>
      <div class="mp-panel__body">
        {!! Form::open([
          'route' => ['merchant.support.dispute.response', $dispute],
          'files' => true,
          'data-toggle' => 'validator',
        ]) !!}

        <div class="form-group">
          {!! Form::label('status', trans('app.status') ?? 'Status') !!}
          {!! Form::select('status', $statuses, $dispute->status, ['class' => 'form-control']) !!}
          <p class="help-block">Use “Appealed” to escalate this ticket to Admin.</p>
        </div>

        <div class="form-group">
          {!! Form::label('reply', 'Update *') !!}
          {!! Form::textarea('reply', null, ['class' => 'form-control', 'rows' => 4, 'required', 'placeholder' => 'Write a ticket update…']) !!}
        </div>

        <div class="form-group">
          {!! Form::label('attachments', trans('app.attachments') ?? 'Attachments') !!}
          {!! Form::file('attachments[]', ['multiple' => true, 'class' => 'form-control']) !!}
        </div>

        <button type="submit" class="btn btn-primary">Submit update</button>
        {!! Form::close() !!}
      </div>
    </div>
  @endif
@endsection
