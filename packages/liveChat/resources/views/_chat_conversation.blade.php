<div id="openChatbox-{{ $chat->customer_id }}" class="row heading">
  <div class="col-sm-2 col-md-1 col-xs-3 heading-avatar">
    <img src="{{ get_avatar_src($chat->customer, 'mini') }}" class="img-circle" alt="{{ trans('app.avatar') }}">
  </div>
  <div class="col-sm-8 col-xs-7 heading-name">
    @if (Gate::allows('view', $chat->customer))
      <a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.show', $chat->customer_id) }}" class="ajax-modal-btn heading-name-meta">{!! $chat->customer->getName() !!}</a>
    @else
      <span class="heading-name-meta">{{ $chat->customer->getName() }}</span>
    @endif
    {{-- <span class="heading-online">Online</span> --}}
  </div>
  <div class="col-sm-1 col-xs-1  heading-dot pull-right">
    {{-- <i class="fa fa-ellipsis-v fa-2x  pull-right" aria-hidden="true"></i> --}}
  </div>
</div>

<div class="row message" id="conversationBox">
  <div class="row message-previous">
    <div class="col-sm-12 previous">
      <a onclick="previous(this)" id="ankitjain28" name="20">
        {{-- Show Previous Message! --}}
      </a>
    </div>
  </div>

  <div class="row message-body">
    <div class="col-sm-12 message-main-receiver">
      <div class="receiver">
        <div class="message-text">
          @php
            $sharePrefix = '[product_share]';
            $chatShare = is_string($chat->message) && str_starts_with($chat->message, $sharePrefix)
                ? json_decode(substr($chat->message, strlen($sharePrefix)), true)
                : null;
          @endphp
          @if (is_array($chatShare))
            <div class="shared-product-card">
              <img class="shared-product-thumb" src="{{ $chatShare['image'] ?? '' }}" alt="{{ $chatShare['title'] ?? 'product' }}">
              <div class="shared-product-meta">
                <div class="shared-product-title">{{ $chatShare['title'] ?? '' }}</div>
                <div class="shared-product-price">{{ $chatShare['price'] ?? '' }}</div>
                <a class="shared-product-link" href="{{ $chatShare['url'] ?? '#' }}" target="_blank">View</a>
              </div>
            </div>
          @else
            @php
              $firstMsgPlain = trim((string) $chat->message);
              $firstReply = $chat->replies->first();
              $hideFirstMsg = ($firstMsgPlain === '' || $firstMsgPlain === '[attachment]')
                && $firstReply
                && $firstReply->relationLoaded('attachments')
                && $firstReply->attachments->isNotEmpty()
                && trim((string) ($firstReply->reply ?? '')) === $firstMsgPlain;
            @endphp
            @unless ($hideFirstMsg)
              {!! $chat->message !!}
            @endunless
          @endif
        </div>
      </div>
      <span class="message-time">
        {{ $chat->created_at->diffForHumans() }}
      </span>
    </div>
  </div>

  @foreach ($chat->replies as $reply)
    <div class="row message-body">
      <div class="col-sm-12 message-main-{{ $reply->customer_id ? 'receiver' : 'sender' }}">
        <div class="{{ $reply->customer_id ? 'receiver' : 'sender' }}">
          <div class="message-text">
          @php
            $replyShare = is_string($reply->reply) && str_starts_with($reply->reply, $sharePrefix)
                ? json_decode(substr($reply->reply, strlen($sharePrefix)), true)
                : null;
          @endphp
          @if (is_array($replyShare))
            <div class="shared-product-card">
              <img class="shared-product-thumb" src="{{ $replyShare['image'] ?? '' }}" alt="{{ $replyShare['title'] ?? 'product' }}">
              <div class="shared-product-meta">
                <div class="shared-product-title">{{ $replyShare['title'] ?? '' }}</div>
                <div class="shared-product-price">{{ $replyShare['price'] ?? '' }}</div>
                <a class="shared-product-link" href="{{ $replyShare['url'] ?? '#' }}" target="_blank">View</a>
              </div>
            </div>
          @else
            @php
              $replyPlain = trim((string) $reply->reply);
              $hideReplyPlain = $reply->relationLoaded('attachments') && $reply->attachments->isNotEmpty()
                && ($replyPlain === '' || $replyPlain === '[attachment]');
            @endphp
            @unless ($hideReplyPlain)
              {!! $reply->reply !!}
            @endunless
          @endif
          @if ($reply->relationLoaded('attachments') && $reply->attachments->isNotEmpty())
            <div class="chat-attachment-list" style="margin-top:8px;">
              @foreach ($reply->attachments as $att)
                @php
                  $url = get_storage_file_url($att->path);
                  $isImg = in_array(strtolower((string) $att->extension), ['jpg','jpeg','png','gif','webp'], true);
                @endphp
                @if ($isImg)
                  <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="" style="max-width:220px;border-radius:4px;"></a>
                @else
                  <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-default btn-xs"><i class="fa fa-paperclip"></i> {{ $att->name ?? trans('theme.attachment') }}</a>
                @endif
              @endforeach
            </div>
          @endif
          </div>
        </div>
        <span class="message-time">
          {{ $reply->updated_at->diffForHumans() }}
        </span>
      </div>
    </div>
  @endforeach
</div>

@can('reply', \Incevio\Package\LiveChat\Models\ChatConversation::class)
  <div class="row reply">
    <div class="col-sm-12" style="padding-left: 0; padding-right: 0;">
      <div id="merchant-chat-attachment-preview" class="chat-attachment-preview" aria-live="polite" aria-hidden="true" style="display:none">
        <div class="chat-attachment-preview-inner">
          <span class="chat-attachment-preview-label">{{ trans('theme.attachment') }}</span>
          <div class="chat-attachment-preview-row">
            <img class="chat-attachment-preview-img" alt="" width="44" height="44">
            <span class="chat-attachment-preview-icon" aria-hidden="true"><i class="fa fa-file-o"></i></span>
            <span class="chat-attachment-preview-name"></span>
            <button type="button" id="merchant_remove_attachment" class="chat-attachment-preview-remove" aria-label="{{ trans('theme.remove') }}">&times;</button>
          </div>
        </div>
      </div>
    </div>
    {!! Form::open(['route' => ['admin.support.chat_conversation.reply', $chat], 'files' => true, 'id' => 'chat-form', 'data-toggle' => 'validator']) !!}
    <div class="col-sm-1 col-xs-1 reply-attachment">
      <label class="btn btn-default btn-sm" style="margin:0;padding:6px 8px;cursor:pointer;" title="{{ __('Attachment') }}">
        <i class="fa fa-paperclip"></i>
        <input type="file" id="merchantChatFile" name="photo" accept="image/*,.pdf,.doc,.docx" style="display:none;">
      </label>
    </div>
    <div class="col-sm-10 col-xs-10 reply-main">
      <textarea id="message" name="message" placeholder="Write your reply here ... " class="form-control" rows="1"></textarea>
    </div>
    <div class="col-sm-1 col-xs-1 reply-send nopadding-left">
      <i class="fa fa-send fa-2x" id="send-btn" aria-hidden="true"></i>
    </div>
    {!! Form::close() !!}
  </div>
@endcan
