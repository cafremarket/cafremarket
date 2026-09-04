<div class="sf-message-thread">
  <div class="sf-message-bubble {{ $message->user_id ? '' : 'sf-message-bubble--me' }}">
    <div>
      <div class="sf-message-bubble__meta">
        <strong>
          @if ($message->user_id)
            @if ($message->shop)
              <a href="{{ route('show.store', $message->shop->slug) }}">
                {!! $message->shop->getQualifiedName(20) !!}
              </a>
            @elseif($message->shop_id)
              {{ trans('theme.store') }}
            @else
              {{ get_platform_title() }}
            @endif
          @else
            @lang('theme.me')
          @endif
        </strong>
        {{ $message->created_at->toDayDateTimeString() }}
      </div>
      <div class="sf-message-bubble__body">
        <h5>{{ $message->subject }}</h5>
        {!! $message->message !!}

        @if ($message->hasAttachments())
          <div class="sf-message-bubble__attach">
            @foreach ($message->attachments as $attachment)
              <a href="{{ route('attachment.download', $attachment) }}" class="btn btn-default btn-xs">
                <i class="fas fa-paperclip" data-toggle="tooltip" data-placement="top" title="{{ $attachment->name }}"></i>
                {{ $attachment->name }}
              </a>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>

  @foreach ($message->replies->sortBy('created_at') as $msg)
    <div class="sf-message-bubble {{ $msg->customer_id ? 'sf-message-bubble--me' : '' }}">
      <div>
        <div class="sf-message-bubble__meta">
          <strong>
            @if ($msg->customer_id)
              @lang('theme.me')
            @else
              @if ($msg->repliable->shop)
                {!! $msg->repliable->shop->getName() !!}
              @elseif($msg->repliable->shop_id)
                {{ trans('theme.store') }}
              @else
                {{ get_platform_title() }}
              @endif
            @endif
          </strong>
          {{ $msg->created_at->toDayDateTimeString() }}
        </div>
        <div class="sf-message-bubble__body">
          {!! $msg->reply !!}

          @if ($msg->attachments->count())
            <div class="sf-message-bubble__attach">
              @foreach ($msg->attachments as $attachment)
                <a href="{{ route('attachment.download', $attachment) }}" class="btn btn-default btn-xs">
                  <i class="fas fa-paperclip" data-toggle="tooltip" data-placement="top" title="{{ $attachment->name }}"></i>
                  {{ $attachment->name }}
                </a>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>
  @endforeach
</div>

<div class="sf-message-reply">
  {!! Form::open(['route' => ['message.reply', $message], 'files' => true, 'id' => 'conversation-form', 'class' => 'sf-form', 'data-toggle' => 'validator']) !!}
  <div class="sf-form-group">
    {!! Form::textarea('reply', null, ['class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.message'), 'rows' => '3', 'maxlength' => 500, 'required']) !!}
    <div class="help-block with-errors"></div>
  </div>
  <div class="sf-form-actions">
    <span></span>
    {!! Form::button(trans('theme.button.send_message'), ['type' => 'submit', 'class' => 'btn sf-btn-primary']) !!}
  </div>
  {!! Form::close() !!}
</div>
