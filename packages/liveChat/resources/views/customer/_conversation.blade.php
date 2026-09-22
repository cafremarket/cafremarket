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
              'type' => method_exists($reply, 'resolvedType') ? $reply->resolvedType() : null,
              'payload' => method_exists($reply, 'resolvedPayload') ? $reply->resolvedPayload() : null,
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
          'type' => null,
          'payload' => null,
      ];
  }
  $lastDayKey = null;
  $replyUrl = route('customer.chat.reply', $chat, false);
  $chatProductsUrl = $shop ? route('chat.products', ['shop' => $shop->id], false) : null;
  $chatOrdersUrl = $shop ? route('chat.orders', ['shop' => $shop->id], false) : null;
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
      if (in_array($item['type'] ?? null, ['order_share', 'product_share'], true) && is_array($item['payload'] ?? null)) {
          $share = $item['payload'];
          $shareType = ($item['type'] === 'order_share') ? 'order' : 'product';
      } elseif (str_starts_with($rawText, $orderSharePrefix)) {
          $share = json_decode(substr($rawText, strlen($orderSharePrefix)), true);
          $shareType = 'order';
      } elseif (str_starts_with($rawText, $productSharePrefix)) {
          $share = json_decode(substr($rawText, strlen($productSharePrefix)), true);
          $shareType = 'product';
      }
      $shareHref = '#';
      if (is_array($share)) {
          if ($shareType === 'order' && !empty($share['order_id'])) {
              $shareHref = route('order.detail', $share['order_id']);
          } elseif (!empty($share['url'])) {
              $shareHref = $share['url'];
          }
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
        @if ($item['type'] === 'location' && is_array($item['payload']))
        @php $loc = $item['payload']; @endphp
        <div class="cpc-share cpc-share--location">
          <div class="cpc-share__icon"><i class="fas fa-map-marker-alt"></i></div>
          <div>
            <div class="cpc-share__title">{{ $loc['label'] ?? 'Location' }}</div>
            <a href="https://www.google.com/maps/search/?api=1&query={{ $loc['lat'] ?? '' }},{{ $loc['lng'] ?? '' }}" target="_blank" rel="noopener">Open in Maps</a>
          </div>
        </div>
      @elseif ($item['type'] === 'contact' && is_array($item['payload']))
        @php $con = $item['payload']; @endphp
        <div class="cpc-share cpc-share--contact">
          <div class="cpc-share__icon"><i class="fas fa-user"></i></div>
          <div>
            <div class="cpc-share__title">{{ $con['name'] ?? 'Contact' }}</div>
            @if (!empty($con['phone']))
              <a href="tel:{{ $con['phone'] }}">{{ $con['phone'] }}</a>
            @endif
          </div>
        </div>
      @elseif (is_array($share) && $shareType === 'order')
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
              <a href="{{ $shareHref }}">{{ __('theme.view_order') }}</a>
            </div>
          </div>
        @elseif (is_array($share))
          <div class="cpc-share">
            <img src="{{ $share['image'] ?? '' }}" alt="">
            <div>
              <div class="cpc-share__title">{{ $share['title'] ?? '' }}</div>
              <div class="cpc-share__price">{{ $share['price'] ?? '' }}</div>
              <a href="{{ $shareHref }}">{{ __('theme.view_product') }}</a>
            </div>
          </div>
        @else
          @unless ($hidePlain)
            <p class="cpc-bubble__text">{!! livechat_linkify_html($plain) !!}</p>
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

<div class="cpc-composer" data-reply-url="{{ $replyUrl }}" data-products-url="{{ $chatProductsUrl }}" data-orders-url="{{ $chatOrdersUrl }}">
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

  <div id="cpc-attach-menu" class="cpc-attach-menu" hidden>
    <button type="button" class="cpc-attach-menu__item" data-action="media">
      <span class="cpc-attach-menu__icon"><i class="fas fa-image"></i></span>
      <span class="cpc-attach-menu__label">{{ __('theme.media') }}</span>
    </button>
    <button type="button" class="cpc-attach-menu__item" data-action="product" @unless($chatProductsUrl) disabled @endunless>
      <span class="cpc-attach-menu__icon"><i class="fas fa-shopping-bag"></i></span>
      <span class="cpc-attach-menu__label">{{ __('theme.chat_share_product') }}</span>
    </button>
    <button type="button" class="cpc-attach-menu__item" data-action="order" @unless($chatOrdersUrl) disabled @endunless>
      <span class="cpc-attach-menu__icon"><i class="fas fa-receipt"></i></span>
      <span class="cpc-attach-menu__label">{{ __('theme.share_order') }}</span>
    </button>
  </div>

  <form id="chat-form" class="cpc-composer__form" method="POST" action="{{ $replyUrl }}" enctype="multipart/form-data" autocomplete="off">
    @csrf
    <input type="hidden" name="parent_id" id="cpc-parent-id" value="">
    <button type="button" class="cpc-composer__attach" id="cpc-attach-toggle" title="{{ trans('theme.attachment') ?? 'Attach' }}" aria-haspopup="true" aria-expanded="false">
      <i class="fas fa-plus"></i>
    </button>
    <input type="file" id="customerChatFile" class="cpc-composer__file" name="photo" accept="image/*,.pdf,.doc,.docx" hidden>
    <textarea id="message" name="message" rows="1" placeholder="{{ trans('theme.placeholder.message') ?? 'Write a message…' }}" maxlength="5000"></textarea>
    <button type="submit" class="cpc-composer__send" id="send-btn" aria-label="Send">
      <i class="fas fa-paper-plane"></i>
    </button>
  </form>
  <p id="cpc-send-error" class="cpc-composer__error" hidden></p>
</div>

<div id="cpc-picker-modal" class="cpc-modal" hidden>
  <div class="cpc-modal__card">
    <div class="cpc-modal__head">
      <strong id="cpc-picker-title">{{ __('theme.chat_share_product') }}</strong>
      <button type="button" class="cpc-modal__close" id="cpc-picker-close" aria-label="Close">&times;</button>
    </div>
    <div class="cpc-modal__body">
      <input type="text" id="cpc-picker-search" class="cpc-modal__input" placeholder="{{ trans('theme.search') ?? 'Search…' }}">
      <div id="cpc-picker-list" class="cpc-picker__list"></div>
    </div>
  </div>
</div>

<style>
  /* This fragment has no shared stylesheet anywhere in the app (it is not
     wired into any inbox/shell page yet) — kept fully self-contained so it
     renders correctly wherever it ends up being included/AJAX-loaded. */
  .cpc-thread__head { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; }
  .cpc-thread__back { border: 0; background: none; cursor: pointer; font-size: 16px; color: #475569; }
  .cpc-thread__avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
  .cpc-thread__peer { display: flex; flex-direction: column; line-height: 1.2; }
  .cpc-thread__peer span { font-size: 12px; color: #64748b; }
  .cpc-thread__store { margin-left: auto; color: #64748b; }
  .cpc-thread__messages { padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; min-height: 200px; }
  .cpc-thread__hint { text-align: center; color: #94a3b8; padding: 24px 0; }
  .cpc-day { text-align: center; margin: 6px 0; }
  .cpc-day span { background: #f1f5f9; color: #64748b; font-size: 11px; padding: 3px 10px; border-radius: 99px; }
  .cpc-bubble { max-width: 78%; }
  .cpc-bubble--in { align-self: flex-start; }
  .cpc-bubble--out { align-self: flex-end; }
  .cpc-bubble__body { background: #f1f5f9; border-radius: 14px; padding: 8px 12px; position: relative; }
  .cpc-bubble--out .cpc-bubble__body { background: #ff6600; color: #fff; }
  .cpc-bubble__text { margin: 0; white-space: pre-wrap; word-break: break-word; }
  .cpc-bubble__body time { display: block; font-size: 10px; opacity: .65; margin-top: 4px; text-align: right; }
  .cpc-atts { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 6px; }
  .cpc-atts img { width: 96px; height: 96px; object-fit: cover; border-radius: 10px; }
  .cpc-atts__file { display: inline-flex; align-items: center; gap: 6px; }
  .cpc-share { display: flex; gap: 10px; align-items: center; background: #fff; border-radius: 10px; padding: 6px; margin-bottom: 6px; }
  .cpc-bubble--out .cpc-share { color: #1e293b; }
  .cpc-share img { width: 44px; height: 44px; object-fit: cover; border-radius: 8px; }
  .cpc-share__icon { width: 36px; height: 36px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #ff6600; flex-shrink: 0; }
  .cpc-share__title { font-weight: 600; font-size: 13px; }
  .cpc-share__price { font-size: 12px; color: #64748b; }
  .cpc-share a { font-size: 12px; color: #ff6600; }
  .cpc-composer { position: relative; display: flex; flex-direction: column; gap: 6px; padding: 10px 12px; border-top: 1px solid #e2e8f0; }
  .cpc-composer__form { display: flex; align-items: center; gap: 8px; }
  .cpc-composer__attach, .cpc-composer__send { border: 0; background: #f1f5f9; color: #475569; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; }
  .cpc-composer__send { background: #ff6600; color: #fff; }
  .cpc-composer__form textarea { flex: 1; resize: none; border: 1px solid #e2e8f0; border-radius: 18px; padding: 8px 14px; max-height: 90px; font: inherit; }
  .cpc-composer__error { color: #dc2626; font-size: 12px; margin: 0; }
  .cpc-composer__preview, .cpc-quote-preview { display: flex; align-items: center; gap: 8px; background: #f1f5f9; border-radius: 10px; padding: 6px 10px; font-size: 12px; }
  .cpc-composer__preview[hidden],
  .cpc-quote-preview[hidden] { display: none !important; }
  /* Beat vendors.css input[type=file]{display:block} that shows a native "Choose File" next to the composer */
  input.cpc-composer__file[type="file"],
  #customerChatFile[type="file"] {
    display: none !important;
    width: 0 !important;
    height: 0 !important;
    opacity: 0 !important;
    position: absolute !important;
    pointer-events: none !important;
  }
  .cpc-attach-menu {
    position: absolute;
    bottom: 100%;
    left: 12px;
    margin-bottom: 8px;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(15,23,42,.18);
    padding: 12px 10px 10px;
    display: grid;
    grid-template-columns: repeat(3, minmax(76px, 1fr));
    gap: 8px 12px;
    min-width: 260px;
    z-index: 20;
    overflow: visible;
  }
  .cpc-attach-menu[hidden] { display: none !important; }
  .cpc-attach-menu__item {
    border: 0;
    background: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    gap: 6px;
    padding: 0 2px;
    min-width: 0;
    width: 100%;
    font-size: 11px;
    line-height: 1.25;
    color: #475569;
    cursor: pointer;
  }
  .cpc-attach-menu__item:disabled { opacity: .4; cursor: not-allowed; }
  .cpc-attach-menu__icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #ff6600;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
  }
  .cpc-attach-menu__label {
    display: block;
    width: 100%;
    max-width: 84px;
    text-align: center;
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
    line-height: 1.2;
  }
  .cpc-modal { position: fixed; inset: 0; background: rgba(15,23,42,.45); display: flex; align-items: center; justify-content: center; z-index: 1000; }
  .cpc-modal[hidden] { display: none !important; }
  .cpc-modal__card { width: min(360px, 92vw); max-height: 80vh; overflow: auto; background: #fff; border-radius: 16px; padding: 0; }
  .cpc-modal__head { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
  .cpc-modal__close { border: 0; background: none; font-size: 18px; cursor: pointer; color: #64748b; }
  .cpc-modal__body { padding: 14px 16px; display: flex; flex-direction: column; gap: 10px; }
  .cpc-modal__input { border: 1px solid #e2e8f0; border-radius: 10px; padding: 9px 12px; font: inherit; width: 100%; box-sizing: border-box; }
  .cpc-modal__foot { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 16px; border-top: 1px solid #e2e8f0; }
  .cpc-modal__btn { border: 0; background: #ff6600; color: #fff; padding: 8px 16px; border-radius: 10px; cursor: pointer; font: inherit; }
  .cpc-modal__btn--ghost { background: #f1f5f9; color: #334155; }
  .cpc-picker__list { display: flex; flex-direction: column; gap: 6px; max-height: 320px; overflow: auto; }
  .cpc-picker__row { display: flex; align-items: center; gap: 10px; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px; background: none; cursor: pointer; text-align: left; width: 100%; }
  .cpc-picker__row img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; }
  .cpc-picker__noimg { width: 40px; height: 40px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8; }
  .cpc-picker__body { display: flex; flex-direction: column; }
  .cpc-picker__title { font-size: 13px; font-weight: 600; }
  .cpc-picker__sub { font-size: 12px; color: #64748b; }
  .cpc-picker__empty { color: #94a3b8; text-align: center; padding: 20px 0; }
</style>

<script>
(function () {
  'use strict';

  // When loaded inside the customer Messages inbox (#customer-chatbox), the parent
  // page owns composer / attach-menu JS (AJAX innerHTML does not run this script).
  if (document.getElementById('customer-chatbox')) {
    var boxOnly = document.getElementById('conversationBox');
    if (boxOnly) boxOnly.scrollTop = boxOnly.scrollHeight;
    return;
  }

  var composer = document.querySelector('.cpc-composer');
  if (!composer) { return; }

  var replyUrl = composer.getAttribute('data-reply-url');
  var productsUrl = composer.getAttribute('data-products-url') || null;
  var ordersUrl = composer.getAttribute('data-orders-url') || null;
  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
  var csrfInput = document.querySelector('#chat-form input[name="_token"]');
  var csrf = (csrfInput && csrfInput.value) || (csrfMeta && csrfMeta.content) || '';

  function qs(sel, el) { return (el || document).querySelector(sel); }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function linkify(s) {
    return esc(s).replace(/(https?:\/\/[^\s<]+)/g, function (url) {
      return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
    });
  }

  function clock(iso) {
    try {
      var d = iso ? new Date(iso) : new Date();
      if (isNaN(d.getTime())) return '';
      var h = d.getHours(), m = d.getMinutes(), ap = h >= 12 ? 'PM' : 'AM';
      h = h % 12; if (!h) h = 12;
      return h + ':' + (m < 10 ? '0' : '') + m + ' ' + ap;
    } catch (e) { return ''; }
  }

  function scrollBox() {
    var box = qs('#conversationBox');
    if (box) box.scrollTop = box.scrollHeight;
  }

  function showError(msg) {
    var el = qs('#cpc-send-error');
    if (!el) return;
    el.hidden = !msg;
    el.textContent = msg || '';
  }

  function bubbleHtml(text, outgoing, meta) {
    meta = meta || {};
    var body = '';
    if (meta.type === 'location' && meta.payload) {
      var loc = meta.payload;
      body = '<div class="cpc-share cpc-share--location"><div class="cpc-share__icon"><i class="fas fa-map-marker-alt"></i></div>' +
        '<div><div class="cpc-share__title">' + esc(loc.label || 'Location') + '</div>' +
        '<a href="https://www.google.com/maps/search/?api=1&query=' + esc(loc.lat) + ',' + esc(loc.lng) + '" target="_blank" rel="noopener">Open in Maps</a></div></div>';
    } else if (meta.type === 'contact' && meta.payload) {
      var con = meta.payload;
      body = '<div class="cpc-share cpc-share--contact"><div class="cpc-share__icon"><i class="fas fa-user"></i></div>' +
        '<div><div class="cpc-share__title">' + esc(con.name || 'Contact') + '</div>' +
        (con.phone ? '<a href="tel:' + esc(con.phone) + '">' + esc(con.phone) + '</a>' : '') + '</div></div>';
    } else if ((meta.type === 'order_share' || meta.type === 'product_share') && meta.payload) {
      var share = meta.payload;
      var isOrder = meta.type === 'order_share';
      var sub = isOrder ? [share.total, share.status].filter(Boolean).join(' · ') : (share.price || '');
      var href = (isOrder && share.order_id) ? ('/order/' + encodeURIComponent(share.order_id)) : (share.url || '#');
      var linkLabel = isOrder ? @json(__('theme.view_order')) : @json(__('theme.view_product'));
      body = '<div class="cpc-share' + (isOrder ? ' cpc-share--order' : '') + '">' +
        (share.image ? '<img src="' + esc(share.image) + '" alt="">' : '') +
        '<div><div class="cpc-share__title">' + esc(share.title || (isOrder ? ('Order #' + (share.order_number || '')) : '')) + '</div>' +
        '<div class="cpc-share__price">' + esc(sub) + '</div>' +
        '<a href="' + esc(href) + '">' + esc(linkLabel) + '</a></div></div>';
    } else {
      var plain = String(text || '').trim();
      if (plain && plain !== '[attachment]') {
        body = '<p class="cpc-bubble__text">' + linkify(plain) + '</p>';
      }
    }
    var cls = outgoing ? 'cpc-bubble cpc-bubble--out' : 'cpc-bubble cpc-bubble--in';
    var attrs = '';
    if (meta.replyId) attrs += ' data-reply-id="' + esc(meta.replyId) + '"';
    if (meta.createdAt) attrs += ' data-created-at="' + esc(meta.createdAt) + '"';
    return '<div class="' + cls + '"' + attrs + '><div class="cpc-bubble__body">' + body +
      '<time>' + esc(meta.time || clock(meta.createdAt)) + '</time></div></div>';
  }

  function appendBubble(html) {
    var box = qs('#conversationBox');
    if (!box) return null;
    var wrap = document.createElement('div');
    wrap.innerHTML = html;
    var node = wrap.firstChild;
    if (node) box.appendChild(node);
    scrollBox();
    return node;
  }

  function sendReply(fields) {
    showError('');
    var nowIso = new Date().toISOString();
    var pendingNode = appendBubble(bubbleHtml(fields.message, true, {
      type: fields.type,
      payload: fields.payload,
      createdAt: nowIso,
      time: clock(nowIso),
    }));

    var fd = new FormData();
    fd.append('message', fields.message || '');
    fd.append('_token', csrf || '');
    if (fields.type) fd.append('type', fields.type);
    if (fields.payload) fd.append('payload', JSON.stringify(fields.payload));
    if (fields.file) fd.append('photo', fields.file);
    var parentInput = qs('#cpc-parent-id');
    if (parentInput && parentInput.value) fd.append('parent_id', parentInput.value);

    return fetch(replyUrl, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf || '' },
    }).then(function (res) {
      return res.text().then(function (text) {
        var data = null;
        try { data = JSON.parse(text); } catch (e) {}
        return { ok: res.ok, status: res.status, data: data };
      });
    }).then(function (result) {
      if (!result.ok) {
        if (pendingNode && pendingNode.parentNode) pendingNode.parentNode.removeChild(pendingNode);
        showError('Could not send (HTTP ' + result.status + '). Please try again.');
        return false;
      }
      if (pendingNode && result.data) {
        if (result.data.reply_id) pendingNode.setAttribute('data-reply-id', String(result.data.reply_id));
        var t = pendingNode.querySelector('time');
        if (t) t.textContent = result.data.time || clock(result.data.created_at);
      }
      return true;
    }).catch(function () {
      if (pendingNode && pendingNode.parentNode) pendingNode.parentNode.removeChild(pendingNode);
      showError('Network error. Message not sent.');
      return false;
    });
  }

  // Text / media composer (existing form)
  var form = qs('#chat-form');
  var fileInput = qs('#customerChatFile');
  var attachPreview = qs('#cpc-attach-preview');
  var attachName = qs('#cpc-attach-name');
  var attachClear = qs('#cpc-attach-clear');

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      if (!fileInput.files || !fileInput.files.length) {
        if (attachPreview) attachPreview.hidden = true;
        return;
      }
      if (attachName) attachName.textContent = fileInput.files[0].name;
      if (attachPreview) attachPreview.hidden = false;
    });
  }
  if (attachClear) {
    attachClear.addEventListener('click', function () {
      if (fileInput) fileInput.value = '';
      if (attachPreview) attachPreview.hidden = true;
    });
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var ta = qs('#message', form);
      var msg = ta && ta.value ? ta.value.trim() : '';
      var hasFile = !!(fileInput && fileInput.files && fileInput.files.length);
      if (!msg && !hasFile) return;
      var file = hasFile ? fileInput.files[0] : null;
      sendReply({ message: msg || (hasFile ? '[attachment]' : ''), file: file }).then(function (ok) {
        if (ok) {
          if (ta) ta.value = '';
          if (fileInput) fileInput.value = '';
          if (attachPreview) attachPreview.hidden = true;
        }
      });
    });

    var ta2 = qs('#message', form);
    if (ta2) {
      ta2.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          form.dispatchEvent(new Event('submit', { cancelable: true }));
        }
      });
    }
  }

  // "+" attachment popover menu
  var attachToggle = qs('#cpc-attach-toggle');
  var attachMenu = qs('#cpc-attach-menu');

  function closeMenu() {
    if (attachMenu) attachMenu.hidden = true;
    if (attachToggle) attachToggle.setAttribute('aria-expanded', 'false');
  }
  function openMenu() {
    if (attachMenu) attachMenu.hidden = false;
    if (attachToggle) attachToggle.setAttribute('aria-expanded', 'true');
  }
  if (attachToggle && attachMenu) {
    attachToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      if (attachMenu.hidden) openMenu(); else closeMenu();
    });
    document.addEventListener('click', function (e) {
      if (!attachMenu.hidden && !attachMenu.contains(e.target) && e.target !== attachToggle) closeMenu();
    });
    attachMenu.addEventListener('click', function (e) {
      var btn = e.target.closest('.cpc-attach-menu__item');
      if (!btn || btn.disabled) return;
      closeMenu();
      var action = btn.getAttribute('data-action');
      if (action === 'media') { if (fileInput) fileInput.click(); }
      else if (action === 'product') { openPickerModal('product'); }
      else if (action === 'order') { openPickerModal('order'); }
    });
  }

  function qsa(el, sel) { return Array.prototype.slice.call(el.querySelectorAll(sel)); }

  // Share Product / Share Order picker modal
  var pickerModal = qs('#cpc-picker-modal');
  var pickerTitle = qs('#cpc-picker-title');
  var pickerSearch = qs('#cpc-picker-search');
  var pickerList = qs('#cpc-picker-list');
  var pickerItems = [];
  var pickerMode = null;

  function closePicker() { if (pickerModal) pickerModal.hidden = true; }
  if (pickerModal) {
    var pickerClose = qs('#cpc-picker-close');
    if (pickerClose) pickerClose.addEventListener('click', closePicker);
  }

  function renderPickerList(items) {
    if (!pickerList) return;
    pickerList.innerHTML = '';
    if (!items.length) {
      pickerList.innerHTML = '<p class="cpc-picker__empty">Nothing found.</p>';
      return;
    }
    items.forEach(function (item, idx) {
      var row = document.createElement('button');
      row.type = 'button';
      row.className = 'cpc-picker__row';
      row.setAttribute('data-idx', String(idx));
      var title = pickerMode === 'order' ? (item.title || ('Order #' + (item.order_number || ''))) : (item.title || '');
      var sub = pickerMode === 'order' ? [item.total, item.status].filter(Boolean).join(' · ') : (item.price || '');
      row.innerHTML = (item.image ? '<img src="' + esc(item.image) + '" alt="">' : '<span class="cpc-picker__noimg"><i class="fas fa-image"></i></span>') +
        '<span class="cpc-picker__body"><span class="cpc-picker__title">' + esc(title) + '</span>' +
        '<span class="cpc-picker__sub">' + esc(sub) + '</span></span>';
      pickerList.appendChild(row);
    });
  }

  var pickerSearchDebounce = null;

  function fetchPickerItems(term) {
    var url = pickerMode === 'order' ? ordersUrl : productsUrl;
    if (!url) {
      if (pickerList) pickerList.innerHTML = '<p class="cpc-picker__empty">Not available.</p>';
      return;
    }
    if (term) { url += (url.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(term); }
    fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(function (json) {
        pickerItems = (json && json.data) || [];
        renderPickerList(pickerItems);
      })
      .catch(function () {
        if (pickerList) pickerList.innerHTML = '<p class="cpc-picker__empty">Could not load.</p>';
      });
  }

  function openPickerModal(mode) {
    pickerMode = mode;
    if (pickerTitle) pickerTitle.textContent = mode === 'order' ? @json(__('theme.share_order')) : @json(__('theme.chat_share_product'));
    if (pickerSearch) { pickerSearch.value = ''; pickerSearch.hidden = false; pickerSearch.placeholder = mode === 'order' ? 'Search by order number…' : 'Search…'; }
    pickerItems = [];
    renderPickerList([]);
    if (pickerModal) pickerModal.hidden = false;
    fetchPickerItems('');
  }

  if (pickerSearch) {
    pickerSearch.addEventListener('input', function () {
      var term = pickerSearch.value.trim();
      if (pickerSearchDebounce) clearTimeout(pickerSearchDebounce);
      pickerSearchDebounce = setTimeout(function () { fetchPickerItems(term); }, 300);
    });
  }

  if (pickerList) {
    pickerList.addEventListener('click', function (e) {
      var row = e.target.closest('.cpc-picker__row');
      if (!row) return;
      var idx = parseInt(row.getAttribute('data-idx'), 10);
      var item = pickerItems[idx];
      if (!item) return;
      closePicker();
      if (pickerMode === 'order') {
        sendReply({ message: '[order_share]' + JSON.stringify(item), type: 'order_share', payload: item });
      } else {
        sendReply({ message: '[product_share]' + JSON.stringify(item), type: 'product_share', payload: item });
      }
    });
  }

  scrollBox();
})();
</script>
