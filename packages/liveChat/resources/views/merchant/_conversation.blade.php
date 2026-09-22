@php
  $threadItems = [];
  if ($chat->replies->isNotEmpty()) {
      foreach ($chat->replies as $reply) {
          $threadItems[] = [
              'id' => $reply->id,
              'text' => (string) ($reply->reply ?? ''),
              'type' => $reply->resolvedType(),
              'payload' => $reply->resolvedPayload(),
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
          'type' => \App\Models\Reply::TYPE_TEXT,
          'payload' => null,
          'is_customer' => true,
          'at' => $chat->created_at,
          'attachments' => collect(),
          'quoted' => null,
          'quote_name' => $chat->customer->getName(),
      ];
  }
  $lastDayKey = null;
  $replyUrl = route('merchant.support.chat_conversation.reply', $chat, false);
  $customOrderUrl = route('merchant.support.chat_conversation.customOrder', $chat, false);
  $inventorySearchUrl = route('merchant.support.chat_conversation.searchInventory', [], false);
  $hideBackButton = $hideBackButton ?? false;

  // Stored billing addresses are HTML (<address>...<br/>...</address>) for
  // display on invoices — strip tags and turn <br> into newlines so a plain
  // <textarea> shows readable text instead of literal markup.
  $orderDefaultBillingAddress = '';
  if ($chat->customer_id) {
      $rawBillingAddress = (string) (\App\Models\Order::where('customer_id', $chat->customer_id)->latest('id')->value('billing_address') ?? '');
      $orderDefaultBillingAddress = trim(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $rawBillingAddress)));
  }

  // Customer's saved addresses for the address selector in the custom order modal
  $orderAddresses = $chat->customer_id
      ? app(\App\Services\ChatCustomOrderService::class)->customerAddresses($chat)
      : ['data' => [], 'default_shipping_address_id' => null, 'default_billing_address_id' => null];

  // Pre-fill Tax/Shipping from the shop's own configured defaults instead of
  // starting every quote at 0 — the seller can still edit before sharing.
  $orderDefaults = app(\App\Services\ChatCustomOrderService::class)->orderDefaults($chat);
  $orderDefaultTax = $orderDefaults['tax'];
  $orderDefaultTaxType = $orderDefaults['tax_type'];
  $orderDefaultShipping = $orderDefaults['shipping'];
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
      $itemType = $item['type'] ?? \App\Models\Reply::TYPE_TEXT;
      $share = in_array($itemType, [\App\Models\Reply::TYPE_PRODUCT_SHARE, \App\Models\Reply::TYPE_ORDER_SHARE], true)
          ? $item['payload']
          : null;
      $shareType = $itemType === \App\Models\Reply::TYPE_ORDER_SHARE ? 'order' : ($share ? 'product' : null);
      $rawText = is_string($item['text']) ? $item['text'] : '';
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
              @php
                // The stored share URL is always the customer-facing storefront
                // page — a vendor viewing their own chat panel needs their own
                // order-management page instead.
                $vendorOrderUrl = ! empty($share['order_id'])
                    ? route('merchant.order.details', $share['order_id'], false)
                    : ($share['url'] ?? '#');
              @endphp
              <a href="{{ $vendorOrderUrl }}" target="_blank" rel="noopener">View order</a>
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
        @elseif ($itemType === \App\Models\Reply::TYPE_LOCATION && is_array($item['payload']))
          <div class="mpc-share mpc-share--location">
            <div class="mpc-share__icon"><i class="fa fa-map-marker"></i></div>
            <div>
              <div class="mpc-share__title">{{ $item['payload']['label'] ?? 'Location' }}</div>
              <a href="https://www.google.com/maps/search/?api=1&query={{ $item['payload']['lat'] ?? 0 }},{{ $item['payload']['lng'] ?? 0 }}" target="_blank" rel="noopener">Open in Maps</a>
            </div>
          </div>
        @elseif ($itemType === \App\Models\Reply::TYPE_CONTACT && is_array($item['payload']))
          <div class="mpc-share mpc-share--contact">
            <div class="mpc-share__icon"><i class="fa fa-user"></i></div>
            <div>
              <div class="mpc-share__title">{{ $item['payload']['name'] ?? 'Contact' }}</div>
              <div class="mpc-share__price">{{ $item['payload']['phone'] ?? '' }}</div>
              @if (!empty($item['payload']['phone']))
                <a href="tel:{{ $item['payload']['phone'] }}">Call</a>
              @endif
            </div>
          </div>
        @else
          @unless ($hidePlain)
            <p class="mpc-bubble__text">{!! livechat_linkify_html($plain) !!}</p>
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

<div class="mpc-composer" data-reply-url="{{ $replyUrl }}" data-custom-order-url="{{ $customOrderUrl }}">
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
    <div class="mpc-attach-menu-wrap">
      <button type="button" class="mpc-attach-toggle" id="mpc-attach-toggle" title="Attach" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-plus"></i>
      </button>
      <label class="mpc-composer__attach" style="display:none">
        <input type="file" id="merchantChatFile" name="photo" accept="image/*,.pdf,.doc,.docx">
      </label>
      <div class="mpc-attach-menu" id="mpc-attach-menu">
        <button type="button" id="mpc-menu-media"><i class="fa fa-paperclip"></i> Media</button>
        <button type="button" id="mpc-menu-share-product"><i class="fa fa-tag"></i> Share Product</button>
        <button type="button" id="mpc-menu-share-order"><i class="fa fa-receipt"></i> Share Order</button>
        <button type="button" id="mpc-menu-order"><i class="fa fa-shopping-bag"></i> Create Order</button>
      </div>
    </div>
    <button type="button" class="mpc-attach-toggle" id="mpc-order-shortcut" title="Create custom order">
      <i class="fa fa-shopping-bag"></i>
    </button>
    <textarea id="message" name="message" rows="1" placeholder="Write a reply…" maxlength="5000"></textarea>
    <button type="submit" class="mpc-composer__send" id="send-btn" aria-label="Send">
      <i class="fa fa-send"></i>
    </button>
  </form>
  <p id="mpc-send-error" class="mpc-composer__error" hidden></p>
</div>

<div class="mpc-modal-backdrop" id="mpc-picker-modal" hidden
     data-products-url="{{ $inventorySearchUrl }}"
     data-orders-url="{{ route('merchant.support.chat_conversation.searchOrders', $chat, false) }}">
  <div class="mpc-modal">
    <h3 id="mpc-picker-title">Share a product</h3>
    <input type="text" id="mpc-picker-search" placeholder="Search…">
    <div id="mpc-picker-list" class="mpc-picker-list"></div>
    <div class="mpc-modal-actions">
      <button type="button" class="mpc-btn-secondary" id="mpc-picker-close">Close</button>
    </div>
  </div>
</div>

<div class="mpc-modal-backdrop" id="mpc-order-modal" hidden
     data-inventory-search-url="{{ $inventorySearchUrl }}"
     data-calculate-totals-url="{{ route('merchant.support.chat_conversation.calculateTotals', [], false) }}"
     data-default-shipping="{{ $orderDefaultShipping }}"
     data-default-tax="{{ $orderDefaultTax }}"
     data-default-tax-type="{{ $orderDefaultTaxType }}">
  <div class="mpc-modal mpc-modal--xwide">
    <h3>Create custom order</h3>
    <p class="mpc-modal-hint">Search your own catalog for real pricing, or add a one-off custom item — every field below is editable.</p>

    <table class="mpc-oi-table">
      <thead>
        <tr>
          <th class="mpc-oi-th-product">Product</th>
          <th class="mpc-oi-th-num">Qty</th>
          <th class="mpc-oi-th-num">Price</th>
          <th class="mpc-oi-th-num">Total</th>
          <th class="mpc-oi-th-action"></th>
        </tr>
      </thead>
      <tbody id="mpc-order-items"></tbody>
    </table>
    <button type="button" class="mpc-order-add-item-btn" id="mpc-order-add-item">
      <i class="fa fa-plus-circle"></i> Add item
    </button>

    <div class="mpc-checkout-summary">
      <div class="mpc-checkout-summary__row">
        <span>Subtotal</span>
        <strong id="mpc-order-subtotal">0.00</strong>
      </div>

      <div class="mpc-checkout-summary__row mpc-checkout-summary__row--input">
        <label for="mpc-order-shipping">
          Shipping cost
          <a href="javascript:void(0);" class="mpc-info-icon" id="mpc-shipping-info" tabindex="0" role="button"
             data-toggle="popover" data-trigger="hover focus click" data-html="true" data-placement="left" data-container="body"
             title="Shipping" data-content="" hidden>
            <i class="fa fa-info-circle"></i>
          </a>
        </label>
        <input type="number" id="mpc-order-shipping" min="0" step="0.01" value="{{ $orderDefaultShipping }}">
      </div>

      <div class="mpc-checkout-summary__row mpc-checkout-summary__row--input">
        <label for="mpc-order-tax">
          Tax
          <a href="javascript:void(0);" class="mpc-info-icon" id="mpc-tax-info" tabindex="0" role="button"
             data-toggle="popover" data-trigger="hover focus click" data-html="true" data-placement="left" data-container="body"
             title="Tax" data-content="" hidden>
            <i class="fa fa-info-circle"></i>
          </a>
        </label>
        <div class="mpc-order-amount-row">
          <input type="number" id="mpc-order-tax" min="0" step="0.01" value="{{ $orderDefaultTax }}">
          <select id="mpc-order-tax-type">
            <option value="amount" {{ $orderDefaultTaxType === 'amount' ? 'selected' : '' }}>Fixed</option>
            <option value="percent" {{ $orderDefaultTaxType === 'percent' ? 'selected' : '' }}>%</option>
          </select>
        </div>
      </div>

      <div class="mpc-checkout-summary__row mpc-checkout-summary__row--input">
        <label for="mpc-order-discount">Discount</label>
        <div class="mpc-order-amount-row">
          <input type="number" id="mpc-order-discount" min="0" step="0.01" value="0">
          <select id="mpc-order-discount-type">
            <option value="amount">Fixed</option>
            <option value="percent">%</option>
          </select>
        </div>
      </div>

      <div class="mpc-checkout-summary__row mpc-checkout-summary__row--grand">
        <span>Grand total</span>
        <strong id="mpc-order-grand-total">0.00</strong>
      </div>
    </div>

    <p class="mpc-modal-hint">The customer picks their own payment method and pays once they open this order — no need to choose one here.</p>

    @if (count($orderAddresses['data']))
      <div class="mpc-addr-section"
           data-default-shipping="{{ $orderAddresses['default_shipping_address_id'] }}"
           data-default-billing="{{ $orderAddresses['default_billing_address_id'] }}">
        <label class="mpc-addr-heading">Shipping address</label>
        @include('liveChat::merchant.partials._address_options', ['name' => 'mpc_ship_addr', 'addresses' => $orderAddresses['data'], 'selected' => $orderAddresses['default_shipping_address_id']])

        <label class="mpc-addr-same">
          <input type="checkbox" id="mpc-order-same-billing" {{ $orderAddresses['default_billing_address_id'] == $orderAddresses['default_shipping_address_id'] ? 'checked' : '' }}>
          <span>Billing address same as shipping</span>
        </label>

        <div id="mpc-bill-addr-wrap" {{ $orderAddresses['default_billing_address_id'] == $orderAddresses['default_shipping_address_id'] ? 'hidden' : '' }}>
          <label class="mpc-addr-heading">Billing address</label>
          @include('liveChat::merchant.partials._address_options', ['name' => 'mpc_bill_addr', 'addresses' => $orderAddresses['data'], 'selected' => $orderAddresses['default_billing_address_id']])
        </div>
      </div>
    @else
      <p class="mpc-addr-empty"><i class="fa fa-info-circle"></i> This customer has no saved address yet — type the billing address below.</p>
      <label for="mpc-order-billing">Billing address</label>
      <textarea id="mpc-order-billing" rows="3" placeholder="Billing address">{{ $orderDefaultBillingAddress }}</textarea>
    @endif

    <label for="mpc-order-note">Note to customer (optional)</label>
    <textarea id="mpc-order-note" rows="2" placeholder="e.g. Thanks for your order! Here's a custom quote…"></textarea>

    <p id="mpc-order-error" class="mpc-composer__error" hidden></p>
    <div class="mpc-modal-actions">
      <button type="button" class="mpc-btn-secondary" id="mpc-order-cancel">Cancel</button>
      <button type="button" class="mpc-btn-primary" id="mpc-order-submit">Create &amp; share</button>
    </div>
  </div>
</div>
