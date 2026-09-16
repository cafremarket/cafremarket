@php
  $productSharePrefix = '[product_share]';
  $orderSharePrefix = '[order_share]';
  $shop = $chat->shop;
  $youLabel = trans('app.you');
  $youLabel = ($youLabel && $youLabel !== 'app.you') ? $youLabel : 'You';
  $sellerLabel = ($shop && $shop->name) ? $shop->name : (trans('theme.seller') ?: 'Seller');
  $threadItems = [];
  if ($chat->replies->isNotEmpty()) {
      foreach ($chat->replies as $reply) {
          $atts = $reply->relationLoaded('attachments') ? $reply->attachments : collect();
          $threadItems[] = [
              'id' => $reply->id,
              'text' => (string) ($reply->reply ?? ''),
              'is_customer' => (bool) $reply->customer_id,
              'at' => $reply->created_at,
              'attachments' => $atts ?: collect(),
              'quoted' => $reply->quoted_reply,
              'quote_name' => $reply->customer_id ? $youLabel : $sellerLabel,
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
          'quote_name' => $youLabel,
      ];
  }
  $lastDayKey = null;
  $replyUrl = route('customer.chat.reply', $chat, false);
@endphp

<header class="cpc-thread__head" id="openChatbox-{{ $chat->id }}"
        data-conversation-id="{{ $chat->id }}"
        data-shop-id="{{ $chat->shop_id }}"
        data-customer-id="{{ $chat->customer_id }}"
        data-ws-room="{{ get_chat_room_name($chat->shop_id.$chat->customer_id) }}">
  <button type="button" class="cpc-thread__back" id="cpc-back-list" aria-label="Back">
    <i class="fas fa-arrow-left"></i>
  </button>
  <img src="{{ $shop ? get_logo_url($shop, 'small') : get_logo_url('system', 'logo') }}" class="cpc-thread__avatar" alt="">
  <div class="cpc-thread__peer">
    <strong>{{ $shop ? $shop->name : (trans('theme.store') ?? 'Store') }}</strong>
    <span>
      @if ($chat->order_id && optional($chat->order)->order_number)
        Order #{{ $chat->order->order_number }}
      @elseif ($shop && method_exists($shop, 'verifiedText'))
        {{ $shop->verifiedText() }}
      @else
        {{ trans('theme.seller') ?? 'Seller' }}
      @endif
    </span>
  </div>
  @if ($shop && $shop->slug)
    <a href="{{ route('show.store', $shop->slug) }}" class="cpc-thread__store" target="_blank" rel="noopener">
      <i class="fas fa-external-link-alt"></i>
    </a>
  @endif
</header>

<div class="cpc-thread__messages" id="conversationBox">
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
      $atts = $item['attachments'] instanceof \Illuminate\Support\Collection
          ? $item['attachments']
          : collect($item['attachments'] ?? []);
      $hidePlain = $atts->isNotEmpty() && ($plain === '' || $plain === '[attachment]');
      // Customer side: my messages are outgoing
      $bubble = $item['is_customer'] ? 'cpc-bubble--out' : 'cpc-bubble--in';
    @endphp

    @if ($dayKey && $dayKey !== $lastDayKey)
      @php $lastDayKey = $dayKey; @endphp
      <div class="cpc-day" data-day="{{ $dayKey }}"><span>{{ livechat_format_day_label($item['at']) }}</span></div>
    @endif

    <div class="cpc-bubble {{ $bubble }}" @if ($item['id']) data-reply-id="{{ $item['id'] }}" data-quote-text="{{ e(livechat_quoted_snippet($plain)) }}" data-quote-name="{{ e($item['quote_name'] ?? '') }}" data-sender-type="{{ $item['is_customer'] ? 'customer' : 'merchant' }}" @endif data-created-at="{{ optional($item['at'])->toIso8601String() }}">
      <div class="cpc-bubble__body">
        @include('liveChat::partials._quoted_reply', ['quoted' => $item['quoted'] ?? null])
        @if (is_array($share) && $shareType === 'order')
          <div class="cpc-share cpc-share--order">
            @if (!empty($share['image']))
              <img src="{{ $share['image'] }}" alt="">
            @endif
            <div>
              <div class="cpc-share__title">{{ $share['title'] ?? ('Order #'.($share['order_number'] ?? '')) }}</div>
              <div class="cpc-share__price">
                @if (!empty($share['total'])){{ $share['total'] }}@endif
                @if (!empty($share['total']) && !empty($share['status'])) · @endif
                @if (!empty($share['status'])){{ $share['status'] }}@endif
              </div>
              <a href="{{ $share['url'] ?? '#' }}" target="_blank" rel="noopener">View order</a>
            </div>
          </div>
        @elseif (is_array($share))
          <div class="cpc-share">
            <img src="{{ $share['image'] ?? '' }}" alt="">
            <div>
              <div class="cpc-share__title">{{ $share['title'] ?? '' }}</div>
              <div class="cpc-share__price">{{ $share['price'] ?? '' }}</div>
              <a href="{{ $share['url'] ?? '#' }}" target="_blank" rel="noopener">View</a>
            </div>
          </div>
        @else
          @unless ($hidePlain)
            <p class="cpc-bubble__text">{{ $plain }}</p>
          @endunless
        @endif

        @if ($atts->isNotEmpty())
          <div class="cpc-atts">
            @foreach ($atts as $att)
              @php
                $url = get_storage_file_url($att->path);
                $isImg = in_array(strtolower((string) $att->extension), ['jpg','jpeg','png','gif','webp'], true);
              @endphp
              @if ($isImg)
                <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt=""></a>
              @else
                <a href="{{ $url }}" target="_blank" rel="noopener" class="cpc-atts__file"><i class="fas fa-paperclip"></i> {{ $att->name ?? 'File' }}</a>
              @endif
            @endforeach
          </div>
        @endif

        <time datetime="{{ optional($item['at'])->toIso8601String() }}">{{ livechat_format_message_time($item['at']) }}</time>
      </div>
    </div>
  @empty
    <div class="cpc-thread__hint">{{ trans('theme.no_messages') ?? 'No messages yet. Say hello.' }}</div>
  @endforelse
</div>

<div class="cpc-composer" data-reply-url="{{ $replyUrl }}">
  <div id="cpc-quote-preview" class="cpc-quote-preview" hidden>
    <div class="cpc-quote-preview__bar"></div>
    <div class="cpc-quote-preview__meta">
      <strong id="cpc-quote-name"></strong>
      <span id="cpc-quote-text"></span>
    </div>
    <button type="button" id="cpc-quote-clear" aria-label="Cancel">&times;</button>
  </div>
  <div id="cpc-attach-preview" class="cpc-composer__preview" hidden>
    <span id="cpc-attach-name"></span>
    <button type="button" id="cpc-attach-clear" aria-label="Remove">&times;</button>
  </div>
  <form id="chat-form" class="cpc-composer__form" method="POST" action="{{ $replyUrl }}" enctype="multipart/form-data" autocomplete="off">
    @csrf
    <input type="hidden" name="parent_id" id="cpc-parent-id" value="">
    <label class="cpc-composer__attach" title="Attachment">
      <i class="fas fa-paperclip"></i>
      <input type="file" id="customerChatFile" name="photo" accept="image/*,.pdf,.doc,.docx">
    </label>
    <textarea id="message" name="message" rows="1" placeholder="{{ trans('theme.placeholder.message') ?? 'Write a message…' }}" maxlength="5000"></textarea>
    <button type="submit" class="cpc-composer__send" id="send-btn" aria-label="Send">
      <i class="fas fa-paper-plane"></i>
    </button>
  </form>
  <p id="cpc-send-error" class="cpc-composer__error" hidden></p>
</div>
