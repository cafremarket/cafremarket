<div id="openChatbox-{{ $chat->customer_id }}" class="mp-chat-thread__head heading">
  <div class="mp-chat-thread__peer">
    <img src="{{ get_avatar_src($chat->customer, 'mini') }}" class="mp-chat-thread__avatar img-circle" alt="{{ trans('app.avatar') }}">
    <div class="heading-name">
      @if (Gate::allows('view', $chat->customer) && ! livechat_is_merchant_panel())
        <a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.show', $chat->customer_id) }}" class="ajax-modal-btn heading-name-meta">{!! $chat->customer->getName() !!}</a>
      @else
        <span class="heading-name-meta">{{ $chat->customer->getName() }}</span>
      @endif
      <span class="mp-chat-thread__sub">{{ trans('app.customer') ?? 'Customer' }}</span>
    </div>
  </div>
</div>

<div class="mp-chat-thread__messages message" id="conversationBox">
  <div class="row message-previous">
    <div class="col-sm-12 previous">
      <a onclick="previous(this)" id="ankitjain28" name="20"></a>
    </div>
  </div>

  @php
    $sharePrefix = '[product_share]';
    // conversation.message is an inbox preview (last text), not a chat bubble.
    $threadItems = [];
    if ($chat->replies->isNotEmpty()) {
        foreach ($chat->replies as $reply) {
            $threadItems[] = [
                'id' => $reply->id,
                'text' => (string) ($reply->reply ?? ''),
                'is_customer' => (bool) $reply->customer_id,
                'at' => $reply->created_at,
                'attachments' => $reply->relationLoaded('attachments') ? $reply->attachments : collect(),
            ];
        }
    } elseif (filled($chat->message)) {
        $threadItems[] = [
            'id' => null,
            'text' => (string) ($chat->message ?? ''),
            'is_customer' => true,
            'at' => $chat->created_at,
            'attachments' => collect(),
        ];
    }
    $lastDayKey = null;
  @endphp

  @foreach ($threadItems as $item)
    @php
      $dayKey = livechat_day_key($item['at']);
      $share = is_string($item['text']) && str_starts_with($item['text'], $sharePrefix)
          ? json_decode(substr($item['text'], strlen($sharePrefix)), true)
          : null;
      $plain = trim((string) $item['text']);
      $atts = $item['attachments'];
      $hidePlain = $atts->isNotEmpty() && ($plain === '' || $plain === '[attachment]');
      $who = $item['is_customer'] ? 'receiver' : 'sender';
      $bubble = $item['is_customer'] ? 'mp-chat-bubble--in' : 'mp-chat-bubble--out';
    @endphp

    @if ($dayKey && $dayKey !== $lastDayKey)
      @php $lastDayKey = $dayKey; @endphp
      <div class="mp-chat-day-sep" data-day="{{ $dayKey }}">
        <span>{{ livechat_format_day_label($item['at']) }}</span>
      </div>
    @endif

    <div class="mp-chat-bubble {{ $bubble }}" @if ($item['id']) data-reply-id="{{ $item['id'] }}" @endif data-created-at="{{ optional($item['at'])->toIso8601String() }}">
      <div class="{{ $who }}">
        <div class="message-text">
          @if (is_array($share))
            <div class="shared-product-card">
              <img class="shared-product-thumb" src="{{ $share['image'] ?? '' }}" alt="{{ $share['title'] ?? 'product' }}">
              <div class="shared-product-meta">
                <div class="shared-product-title">{{ $share['title'] ?? '' }}</div>
                <div class="shared-product-price">{{ $share['price'] ?? '' }}</div>
                <a class="shared-product-link" href="{{ $share['url'] ?? '#' }}" target="_blank">View</a>
              </div>
            </div>
          @else
            @unless ($hidePlain)
              {{ $plain }}
            @endunless
          @endif
          @if ($atts->isNotEmpty())
            <div class="chat-attachment-list">
              @foreach ($atts as $att)
                @php
                  $url = get_storage_file_url($att->path);
                  $isImg = in_array(strtolower((string) $att->extension), ['jpg','jpeg','png','gif','webp'], true);
                @endphp
                @if ($isImg)
                  <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="" class="mp-chat-att-img"></a>
                @else
                  <a href="{{ $url }}" target="_blank" rel="noopener" class="mp-chat-att-file"><i class="fa fa-paperclip"></i> {{ $att->name ?? trans('theme.attachment') }}</a>
                @endif
              @endforeach
            </div>
          @endif
        </div>
        <time class="message-time" datetime="{{ optional($item['at'])->toIso8601String() }}">{{ livechat_format_message_time($item['at']) }}</time>
      </div>
    </div>
  @endforeach
</div>

@can('reply', \Incevio\Package\LiveChat\Models\ChatConversation::class)
  <div class="mp-chat-composer reply">
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
    {!! Form::open([
      'url' => route(livechat_support_route_name('chat_conversation.reply'), $chat, false),
      'files' => true,
      'id' => 'chat-form',
      'class' => 'mp-chat-composer__form',
    ]) !!}
      <label class="mp-chat-composer__attach reply-attachment" title="{{ __('Attachment') }}">
        <i class="fa fa-paperclip"></i>
        <input type="file" id="merchantChatFile" name="photo" accept="image/*,.pdf,.doc,.docx">
      </label>
      <div class="mp-chat-composer__field reply-main">
        <textarea id="message" name="message" placeholder="Write your reply…" class="form-control" rows="1" autocomplete="off"></textarea>
      </div>
      <button type="button" class="mp-chat-composer__send reply-send" id="send-btn" aria-label="{{ trans('app.send') ?? 'Send' }}">
        <i class="fa fa-send" aria-hidden="true"></i>
      </button>
    {!! Form::close() !!}
  </div>
@endcan
