@php
  $productSharePrefix = '[product_share]';
  $orderSharePrefix = '[order_share]';
  $threadItems = [];
  if ($chat->replies->isNotEmpty()) {
      foreach ($chat->replies as $reply) {
          $threadItems[] = [
              'id' => $reply->id,
              'text' => (string) ($reply->reply ?? ''),
              'is_customer' => (bool) $reply->customer_id,
              'at' => $reply->created_at,
              'attachments' => $reply->relationLoaded('attachments') ? $reply->attachments : collect(),
              'quoted' => $reply->quoted_reply,
              'quote_name' => $reply->customer_id
                  ? $chat->customer->getName()
                  : (trans('app.you') ?: 'You'),
          ];
      }
  } elseif (filled($chat->message)) {
      $threadItems[] = [
          'id' => null,
          'text' => (string) ($chat->message ?? ''),
          'is_customer' => true,
          'at' => $chat->created_at,
          'attachments' => collect(),
          'quoted' => null,
          'quote_name' => $chat->customer->getName(),
      ];
  }
  $lastDayKey = null;
  $replyUrl = route('merchant.support.chat_conversation.reply', $chat, false);
  $hideBackButton = $hideBackButton ?? false;
@endphp

<header class="mpc-thread__head" id="openChatbox-{{ $chat->id }}" data-customer-id="{{ $chat->customer_id }}" data-conversation-id="{{ $chat->id }}" data-order-id="{{ $chat->order_id }}">
  @unless ($hideBackButton)
  <button type="button" class="mpc-thread__back" id="mpc-back-list" aria-label="Back">
    <i class="fa fa-arrow-left"></i>
  </button>
  @endunless
  <img src="{{ get_avatar_src($chat->customer, 'mini') }}" class="mpc-thread__avatar" alt="">
  <div class="mpc-thread__peer">
    <strong>{{ $chat->customer->getName() }}</strong>
    <span>
      @if (!empty($orderContextNumber))
        Order #{{ $orderContextNumber }}
      @elseif ($chat->order_id && optional($chat->order)->order_number)
        Order #{{ $chat->order->order_number }}
      @else
        {{ trans('app.customer') ?? 'Customer' }}
      @endif
    </span>
  </div>
</header>

<div class="mpc-thread__messages" id="conversationBox">
  @forelse ($threadItems as $item)
    @php
      $dayKey = livechat_day_key($item['at']);
      $shareType = null;
      $share = null;
      $rawText = is_string($item['text']) ? $item['text'] : '';
      if (str_starts_with($rawText, $orderSharePrefix)) {
          $share = json_decode(substr($rawText, strlen($orderSharePrefix)), true);
          $shareType = 'order';
      } elseif (str_starts_with($rawText, $productSharePrefix)) {
          $share = json_decode(substr($rawText, strlen($productSharePrefix)), true);
          $shareType = 'product';
      }
      $plain = trim($rawText);
      $atts = $item['attachments'];
      $hidePlain = $atts->isNotEmpty() && ($plain === '' || $plain === '[attachment]');
      $bubble = $item['is_customer'] ? 'mpc-bubble--in' : 'mpc-bubble--out';
    @endphp

    @if ($dayKey && $dayKey !== $lastDayKey)
      @php $lastDayKey = $dayKey; @endphp
      <div class="mpc-day" data-day="{{ $dayKey }}"><span>{{ livechat_format_day_label($item['at']) }}</span></div>
    @endif

    <div class="mpc-bubble {{ $bubble }}" @if ($item['id']) data-reply-id="{{ $item['id'] }}" data-quote-text="{{ e(livechat_quoted_snippet($plain)) }}" data-quote-name="{{ e($item['quote_name'] ?? '') }}" data-sender-type="{{ $item['is_customer'] ? 'customer' : 'merchant' }}" @endif data-created-at="{{ optional($item['at'])->toIso8601String() }}">
      <div class="mpc-bubble__body">
        @include('liveChat::partials._quoted_reply', ['quoted' => $item['quoted'] ?? null])
        @if (is_array($share) && $shareType === 'order')
          <div class="mpc-share mpc-share--order">
            @if (!empty($share['image']))
              <img src="{{ $share['image'] }}" alt="">
            @endif
            <div>
              <div class="mpc-share__title">{{ $share['title'] ?? ('Order #'.($share['order_number'] ?? '')) }}</div>
              <div class="mpc-share__price">
                @if (!empty($share['total'])){{ $share['total'] }}@endif
                @if (!empty($share['total']) && !empty($share['status'])) · @endif
                @if (!empty($share['status'])){{ $share['status'] }}@endif
              </div>
              <a href="{{ $share['url'] ?? '#' }}" target="_blank" rel="noopener">View order</a>
            </div>
          </div>
        @elseif (is_array($share))
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
  <div id="mpc-quote-preview" class="mpc-quote-preview" hidden>
    <div class="mpc-quote-preview__bar"></div>
    <div class="mpc-quote-preview__meta">
      <strong id="mpc-quote-name"></strong>
      <span id="mpc-quote-text"></span>
    </div>
    <button type="button" id="mpc-quote-clear" aria-label="Cancel">&times;</button>
  </div>
  <div id="mpc-attach-preview" class="mpc-composer__preview" hidden>
    <span id="mpc-attach-name"></span>
    <button type="button" id="mpc-attach-clear" aria-label="Remove">&times;</button>
  </div>
  <form id="chat-form" class="mpc-composer__form" method="POST" action="{{ $replyUrl }}" enctype="multipart/form-data" autocomplete="off">
    @csrf
    <input type="hidden" name="parent_id" id="mpc-parent-id" value="">
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
