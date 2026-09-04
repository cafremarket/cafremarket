@php
  $sharePrefix = '[product_share]';
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
  $replyUrl = route('merchant.support.chat_conversation.reply', $chat, false);
@endphp

<header class="mpc-thread__head" id="openChatbox-{{ $chat->customer_id }}" data-customer-id="{{ $chat->customer_id }}" data-conversation-id="{{ $chat->id }}">
  <button type="button" class="mpc-thread__back" id="mpc-back-list" aria-label="Back">
    <i class="fa fa-arrow-left"></i>
  </button>
  <img src="{{ get_avatar_src($chat->customer, 'mini') }}" class="mpc-thread__avatar" alt="">
  <div class="mpc-thread__peer">
    <strong>{{ $chat->customer->getName() }}</strong>
    <span>{{ trans('app.customer') ?? 'Customer' }}</span>
  </div>
</header>

<div class="mpc-thread__messages" id="conversationBox">
  @forelse ($threadItems as $item)
    @php
      $dayKey = livechat_day_key($item['at']);
      $share = is_string($item['text']) && str_starts_with($item['text'], $sharePrefix)
          ? json_decode(substr($item['text'], strlen($sharePrefix)), true)
          : null;
      $plain = trim((string) $item['text']);
      $atts = $item['attachments'];
      $hidePlain = $atts->isNotEmpty() && ($plain === '' || $plain === '[attachment]');
      $bubble = $item['is_customer'] ? 'mpc-bubble--in' : 'mpc-bubble--out';
    @endphp

    @if ($dayKey && $dayKey !== $lastDayKey)
      @php $lastDayKey = $dayKey; @endphp
      <div class="mpc-day" data-day="{{ $dayKey }}"><span>{{ livechat_format_day_label($item['at']) }}</span></div>
    @endif

    <div class="mpc-bubble {{ $bubble }}" @if ($item['id']) data-reply-id="{{ $item['id'] }}" @endif data-created-at="{{ optional($item['at'])->toIso8601String() }}">
      <div class="mpc-bubble__body">
        @if (is_array($share))
          <div class="mpc-share">
            <img src="{{ $share['image'] ?? '' }}" alt="">
            <div>
              <div class="mpc-share__title">{{ $share['title'] ?? '' }}</div>
              <div class="mpc-share__price">{{ $share['price'] ?? '' }}</div>
              <a href="{{ $share['url'] ?? '#' }}" target="_blank" rel="noopener">View</a>
            </div>
          </div>
        @else
          @unless ($hidePlain)
            <p class="mpc-bubble__text">{{ $plain }}</p>
          @endunless
        @endif

        @if ($atts->isNotEmpty())
          <div class="mpc-atts">
            @foreach ($atts as $att)
              @php
                $url = get_storage_file_url($att->path);
                $isImg = in_array(strtolower((string) $att->extension), ['jpg','jpeg','png','gif','webp'], true);
              @endphp
              @if ($isImg)
                <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt=""></a>
              @else
                <a href="{{ $url }}" target="_blank" rel="noopener" class="mpc-atts__file"><i class="fa fa-paperclip"></i> {{ $att->name ?? 'File' }}</a>
              @endif
            @endforeach
          </div>
        @endif

        <time datetime="{{ optional($item['at'])->toIso8601String() }}">{{ livechat_format_message_time($item['at']) }}</time>
      </div>
    </div>
  @empty
    <div class="mpc-thread__hint">No messages yet. Say hello.</div>
  @endforelse
</div>

<div class="mpc-composer" data-reply-url="{{ $replyUrl }}">
  <div id="mpc-attach-preview" class="mpc-composer__preview" hidden>
    <span id="mpc-attach-name"></span>
    <button type="button" id="mpc-attach-clear" aria-label="Remove">&times;</button>
  </div>
  <form id="chat-form" class="mpc-composer__form" method="POST" action="{{ $replyUrl }}" enctype="multipart/form-data" autocomplete="off">
    @csrf
    <label class="mpc-composer__attach" title="Attachment">
      <i class="fa fa-paperclip"></i>
      <input type="file" id="merchantChatFile" name="photo" accept="image/*,.pdf,.doc,.docx">
    </label>
    <textarea id="message" name="message" rows="1" placeholder="Write a reply…" maxlength="5000"></textarea>
    <button type="submit" class="mpc-composer__send" id="send-btn" aria-label="Send">
      <i class="fa fa-send"></i>
    </button>
  </form>
  <p id="mpc-send-error" class="mpc-composer__error" hidden></p>
</div>
