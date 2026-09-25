<div id="zcart_chat" class="sf-livechat">
  <div id="chat-window" class="chat">
    <div class="chat_header">
      <div class="chat_option">
        <div class="header_img">
          <img src="{{ get_storage_file_url(optional($shop->image)->path, 'thumbnail') }}" alt="{{ $shop->name }}" />
        </div>
        <div class="chat_header_text">
          <span id="chat_head">{{ $shop->name }}</span>
          <span class="agent">{{ optional($agent)->getName() ?? trans('theme.seller') }} <span class="online">· {{ $agent_status }}</span></span>
        </div>
        <button type="button" class="chat_header_close" id="sf_livechat_close" aria-label="{{ trans('theme.livechat.close_chat') }}">&times;</button>
      </div>
    </div>

    <div id="chat_conversation" class="chat_converse">
      @unless (Auth::guard('customer')->check())
        <div class="chat_login_prompt">
          <p>{!! trans('theme.login_to_chat') !!}</p>
          <a href="javascript:void(0)" class="btn btn-primary chat_login_btn" data-toggle="modal" data-target="#loginModal">{{ trans('theme.button.login') }}</a>
        </div>
      @else
        <p class="chat_connecting text-primary">{!! trans('theme.connecting') !!}</p>
      @endunless
    </div>

    @if (Auth::guard('customer')->check())
      @isset($product)
        <div class="chat-product-share">
          <div class="chat-product-share-title">{{ trans('theme.livechat.ask_about_product') }}</div>
          <div class="chat-product-share-card">
            <div class="chat-product-share-media">
              <img src="{{ get_storage_file_url(optional($product->image)->path, 'tiny_thumb') }}" alt="{{ $product->title }}">
            </div>
            <div class="chat-product-share-body">
              <div class="chat-product-share-name">{{ \Illuminate\Support\Str::limit($product->title, 34) }}</div>
              <div class="chat-product-share-price">{{ get_formated_currency($product->current_sale_price(), 2) }}</div>
            </div>
            <div class="chat-product-share-actions">
              <button id="fchat_share_product" class="chat-product-share-btn" type="button" aria-label="{{ trans('theme.livechat.share_product_details') }}">
                {{ trans('theme.livechat.share') }}
              </button>
              <button type="button" id="fchat_dismiss_product_share" class="chat-product-share-dismiss" aria-label="{{ trans('theme.livechat.dismiss_product_preview') }}">&times;</button>
            </div>
          </div>
        </div>
      @endisset
      @isset($order)
        @php
          try {
              $orderSharePayload = livechat_build_order_share_payload($order);
          } catch (\Throwable $e) {
              report($e);
              $orderSharePayload = [
                  'order_id' => $order->id,
                  'order_number' => $order->order_number,
                  'title' => 'Order #'.$order->order_number,
                  'status' => '',
                  'total' => '',
                  // Built from the current request's own host, not config('app.url') —
                  // see OrderChatSyncService::buildOrderSharePayload() for why.
                  'url' => request()->getSchemeAndHttpHost().'/order/'.$order->id,
                  'image' => '',
              ];
          }
        @endphp
        <div class="chat-product-share chat-order-share">
          <div class="chat-product-share-title">{{ trans('theme.livechat.share_order_with_seller') }}</div>
          <div class="chat-product-share-card">
            <div class="chat-product-share-media">
              @if (!empty($orderSharePayload['image']))
                <img src="{{ $orderSharePayload['image'] }}" alt="{{ $orderSharePayload['title'] }}">
              @else
                <i class="fa fa-shopping-bag" style="font-size:22px;opacity:.5;"></i>
              @endif
            </div>
            <div class="chat-product-share-body">
              <div class="chat-product-share-name">{{ $orderSharePayload['title'] }}</div>
              <div class="chat-product-share-price">
                {{ $orderSharePayload['total'] }}
                @if (!empty($orderSharePayload['status']))
                  · {{ $orderSharePayload['status'] }}
                @endif
              </div>
            </div>
            <div class="chat-product-share-actions">
              <button id="fchat_share_order" class="chat-product-share-btn" type="button" aria-label="{{ trans('theme.livechat.share_order_details') }}">
                {{ trans('theme.livechat.share') }}
              </button>
              <button type="button" id="fchat_dismiss_order_share" class="chat-product-share-dismiss" aria-label="{{ trans('theme.livechat.dismiss_order_preview') }}">&times;</button>
            </div>
          </div>
        </div>
      @endisset
      <div class="fchat_field chat-composer">
        <div class="chat-composer-inner">
          <div id="chat-attachment-preview" class="chat-attachment-preview" aria-live="polite" aria-hidden="true" style="display:none">
            <div class="chat-attachment-preview-inner">
              <span class="chat-attachment-preview-label">{{ trans('theme.attachment') }}</span>
              <div class="chat-attachment-preview-row">
                <img class="chat-attachment-preview-img" alt="" width="44" height="44">
                <span class="chat-attachment-preview-icon" aria-hidden="true"><i class="fa fa-file-o"></i></span>
                <span class="chat-attachment-preview-name"></span>
                <button type="button" id="fchat_remove_attachment" class="chat-attachment-preview-remove" aria-label="{{ trans('theme.livechat.remove_attachment') }}">&times;</button>
              </div>
            </div>
          </div>
          <div class="chat-composer-row">
            <div class="chat-attach-wrap">
              <button type="button" id="chat_attach_toggle" class="chat-composer-btn chat-composer-btn--attach" title="{{ trans('theme.livechat.attach') }}" aria-haspopup="true" aria-expanded="false">
                <span class="chat-composer-btn-icon" aria-hidden="true"><i class="fa fa-plus"></i></span>
                <span class="chat-sr-only">{{ trans('theme.livechat.attachment_options') }}</span>
              </button>
              <div id="chat_attach_menu" class="chat-attach-menu" hidden>
                <label id="chat_composer_attach" class="chat-attach-menu-item" title="{{ trans('theme.livechat.media') }}">
                  <input type="file" id="chatBoxFile" name="photo" class="chat-composer-file-input" accept="image/*,.pdf,.doc,.docx" tabindex="-1">
                  <span class="chat-attach-menu-icon" aria-hidden="true"><i class="fa fa-image"></i></span>
                  <span class="chat-attach-menu-label">{{ trans('theme.livechat.media') }}</span>
                </label>
                <button type="button" id="chat_attach_product" class="chat-attach-menu-item">
                  <span class="chat-attach-menu-icon" aria-hidden="true"><i class="fa fa-shopping-bag"></i></span>
                  <span class="chat-attach-menu-label">{{ trans('theme.livechat.share_product') }}</span>
                </button>
                <button type="button" id="chat_attach_order" class="chat-attach-menu-item">
                  <span class="chat-attach-menu-icon" aria-hidden="true"><i class="fa fa-receipt"></i></span>
                  <span class="chat-attach-menu-label">{{ trans('theme.livechat.share_order') }}</span>
                </button>
              </div>
            </div>
            <input id="chatBoxMsg" name="chat_message" type="text" placeholder="{{ trans('theme.livechat.send_a_message') }}" class="chat_field chat_message chat-composer-msg" aria-label="{{ trans('theme.livechat.message_input') }}" autocomplete="off">
            <button type="button" id="fchat_send" class="chat-composer-btn chat-composer-btn--send" aria-label="{{ trans('theme.livechat.send_message') }}">
              <span class="chat-composer-btn-icon" aria-hidden="true"><i class="fa fa-paper-plane"></i></span>
            </button>
          </div>
        </div>
      </div>

      <div id="chat_picker_modal" class="chat-modal" hidden>
        <div class="chat-modal-card">
          <div class="chat-modal-head">
            <span id="chat_picker_title">{{ trans('theme.livechat.share') }}</span>
            <button type="button" class="chat-modal-close" data-modal-close aria-label="{{ trans('theme.livechat.close') }}">&times;</button>
          </div>
          <div class="chat-modal-body">
            <input type="text" id="chat_picker_search" class="chat-modal-input" placeholder="{{ trans('theme.livechat.search') }}">
            <div id="chat_picker_list" class="chat-picker-list">
              <p class="chat-picker-empty">{{ trans('theme.livechat.loading') }}</p>
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>

  <a id="chatbox" class="fchat sf-livechat-fab" aria-label="{{ trans('theme.livechat.open_chat') }}">
    <i class="chat-icon fas fa-comment"></i>
  </a>
</div>

<script type="text/javascript">
  "use strict";
  var LC_I18N = @json(trans('theme.livechat'));
  window.socketConnected = window.socketConnected || false;
  var agent_avatar = $('<div>').addClass('chat_avatar');
  $('<img/>').attr('src', "{{ get_storage_file_url(optional($shop->image)->path, 'thumbnail') }}").appendTo(agent_avatar);

  function updateScroll() {
    var element = document.getElementById("chat_conversation");
    if (!element) {
      return;
    }
    function scrollNow() {
      element.scrollTop = element.scrollHeight;
    }
    scrollNow();
    if (window.requestAnimationFrame) {
      window.requestAnimationFrame(function() {
        scrollNow();
        window.setTimeout(scrollNow, 0);
        window.setTimeout(scrollNow, 50);
        window.setTimeout(scrollNow, 200);
        window.setTimeout(scrollNow, 500);
      });
    } else {
      window.setTimeout(scrollNow, 0);
      window.setTimeout(scrollNow, 100);
    }
  }

  window.updateScroll = updateScroll;

  function attachChatAutoScrollObserver() {
    var el = document.getElementById("chat_conversation");
    if (!el || el._chatAutoScrollObserver) {
      return;
    }
    el._chatAutoScrollObserver = true;
    var timer = null;
    var obs = new MutationObserver(function() {
      if (timer) {
        clearTimeout(timer);
      }
      timer = setTimeout(function() {
        if (typeof window.updateScroll === "function") {
          window.updateScroll();
        }
      }, 40);
    });
    obs.observe(el, { childList: true, subtree: true });
  }

  ;
  (function($, window, document) {
    $(document).ready(function() {
      attachChatAutoScrollObserver();

      var chatPoller = null;
      var isSendingMessage = false;
      var sharePrefix = '[product_share]';
      var orderSharePrefix = '[order_share]';
      @php
        $chatSharePayload = null;
        $chatShareStorageKey = null;
        $chatOrderSharePayload = null;
        $chatOrderShareStorageKey = null;
        if (isset($product)) {
            $chatSharePayload = [
                'title' => $product->title,
                'price' => get_formated_currency($product->current_sale_price(), 2),
                'url' => storefront_product_url($product),
                'image' => get_storage_file_url(optional($product->image)->path, 'tiny_thumb'),
            ];
            $chatShareStorageKey = 'chat_shared_product_'.$product->id;
        }
        if (isset($order)) {
            try {
                $chatOrderSharePayload = livechat_build_order_share_payload($order);
            } catch (\Throwable $e) {
                report($e);
                $chatOrderSharePayload = [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'title' => 'Order #'.$order->order_number,
                    'status' => '',
                    'total' => '',
                    'url' => request()->getSchemeAndHttpHost().'/order/'.$order->id,
                    'image' => '',
                ];
            }
            $chatOrderShareStorageKey = 'chat_shared_order_'.$order->id;
        }
      @endphp
      var shareProductPayload = @json($chatSharePayload);
      var shareStorageKey = @json($chatShareStorageKey);
      var shareProductMessage = shareProductPayload ? (sharePrefix + JSON.stringify(shareProductPayload)) : null;
      var shareOrderPayload = @json($chatOrderSharePayload);
      var shareOrderStorageKey = @json($chatOrderShareStorageKey);
      var shareOrderMessage = shareOrderPayload ? (orderSharePrefix + JSON.stringify(shareOrderPayload)) : null;

      var ChatAttachmentPreview = (function() {
        var objectUrl = null;
        var $strip = function() {
          return $('#chat-attachment-preview');
        };

        function revoke() {
          if (objectUrl) {
            try {
              URL.revokeObjectURL(objectUrl);
            } catch (e) {}
            objectUrl = null;
          }
        }

        function hideStrip($p) {
          $p.removeClass('chat-attachment-preview--visible').attr('aria-hidden', 'true');
          var el = $p[0];
          if (el) {
            el.style.display = 'none';
          }
          $p.find('.chat-attachment-preview-img').removeAttr('src').hide();
          $p.find('.chat-attachment-preview-icon').hide();
          $p.find('.chat-attachment-preview-name').text('');
        }

        function showStrip($p) {
          var el = $p[0];
          if (el) {
            el.style.display = 'block';
          }
        }

        return {
          clear: function() {
            revoke();
            var fi = document.getElementById('chatBoxFile');
            if (fi) {
              fi.value = '';
            }
            hideStrip($strip());
          },
          updateFromInput: function() {
            var fi = document.getElementById('chatBoxFile');
            var $p = $strip();
            if (!fi || !fi.files || !fi.files.length) {
              revoke();
              hideStrip($p);
              return;
            }
            var f = fi.files[0];
            var name = (f && f.name) ? f.name : 'File';
            revoke();
            $p.find('.chat-attachment-preview-name').text(name);
            var isImg = (f.type && f.type.indexOf('image/') === 0) ||
              /\.(jpe?g|png|gif|webp|bmp|svg)$/i.test(name);
            if (isImg) {
              objectUrl = URL.createObjectURL(f);
              $p.find('.chat-attachment-preview-img').attr('src', objectUrl).show();
              $p.find('.chat-attachment-preview-icon').hide();
            } else {
              $p.find('.chat-attachment-preview-img').removeAttr('src').hide();
              $p.find('.chat-attachment-preview-icon').show();
              var ext = (name.split('.').pop() || '').toLowerCase();
              var iconClass = 'fa fa-file-o';
              if (ext === 'pdf') {
                iconClass = 'fa fa-file-pdf-o';
              } else if (ext === 'doc' || ext === 'docx') {
                iconClass = 'fa fa-file-word-o';
              }
              $p.find('.chat-attachment-preview-icon i').attr('class', iconClass);
            }
            $p.addClass('chat-attachment-preview--visible').attr('aria-hidden', 'false');
            showStrip($p);
          }
        };
      })();

      function clearAttachmentPreview() {
        ChatAttachmentPreview.clear();
      }

      function refreshAttachmentPreviewStrip() {
        ChatAttachmentPreview.updateFromInput();
      }

      function getSharedPayload(message) {
        if (message == null || message === '') return null;
        var raw = String(message).replace(/^\uFEFF/, '');
        var prefixes = [sharePrefix, orderSharePrefix];
        for (var p = 0; p < prefixes.length; p++) {
          var prefix = prefixes[p];
          var idx = raw.indexOf(prefix);
          if (idx === -1) continue;
          var rest = raw.substring(idx + prefix.length).trim();
          try {
            var parsed = JSON.parse(rest);
            if (parsed && typeof parsed === 'object') {
              parsed.__shareType = prefix === orderSharePrefix ? 'order' : 'product';
              return parsed;
            }
          } catch (e) {
            var start = rest.indexOf('{');
            var end = rest.lastIndexOf('}');
            if (start === -1 || end === -1 || end <= start) continue;
            try {
              var parsed2 = JSON.parse(rest.substring(start, end + 1));
              if (parsed2 && typeof parsed2 === 'object') {
                parsed2.__shareType = prefix === orderSharePrefix ? 'order' : 'product';
                return parsed2;
              }
            } catch (e2) {}
          }
        }
        return null;
      }

      function buildAttachmentBlock(attachments) {
        if (!attachments || !attachments.length) return null;
        var wrap = $('<div>').addClass('chat-attachment-block');
        attachments.forEach(function(att) {
          var url = '';
          if (att.path) {
            url = '/image/' + att.path.split('/').map(function(seg) {
              return encodeURIComponent(seg);
            }).join('/');
          }
          var ext = (att.extension || '').toLowerCase();
          var isImg = ['jpg','jpeg','png','gif','webp'].indexOf(ext) !== -1;
          if (isImg && url) {
            $('<a>').addClass('chat-att-media').attr('href', url).attr('target', '_blank').attr('rel', 'noopener')
              .append($('<img>').addClass('chat-att-thumb').attr('src', url).attr('alt', '').attr('loading', 'lazy'))
              .appendTo(wrap);
          } else if (url) {
            $('<a>').addClass('chat-att-link').attr('href', url).attr('target', '_blank').attr('rel', 'noopener')
              .text(att.name || 'Download').appendTo(wrap);
          }
        });
        return wrap;
      }

      function escText(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
      }

      function linkifyText(s) {
        return escText(s).replace(/(https?:\/\/[^\s<]+)/g, function (url) {
          return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
        });
      }

      function formatChatClock(isoOrDate) {
        try {
          var d = isoOrDate ? new Date(isoOrDate) : new Date();
          if (isNaN(d.getTime())) d = new Date();
          return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        } catch (e) {
          return '';
        }
      }

      function formatChatDayLabel(isoOrDate) {
        try {
          var d = isoOrDate ? new Date(isoOrDate) : new Date();
          if (isNaN(d.getTime())) return '';
          var today = new Date();
          var yday = new Date();
          yday.setDate(today.getDate() - 1);
          var sameDay = function(a, b) {
            return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
          };
          if (sameDay(d, today)) return 'Today';
          if (sameDay(d, yday)) return 'Yesterday';
          return d.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        } catch (e) {
          return '';
        }
      }

      function chatDayKey(isoOrDate) {
        try {
          var d = isoOrDate ? new Date(isoOrDate) : new Date();
          if (isNaN(d.getTime())) return '';
          var m = d.getMonth() + 1;
          var day = d.getDate();
          return d.getFullYear() + '-' + (m < 10 ? '0' : '') + m + '-' + (day < 10 ? '0' : '') + day;
        } catch (e) {
          return '';
        }
      }

      function ensureStorefrontDaySep(isoOrDate) {
        var $box = $("#chat_conversation");
        if (!$box.length) return;
        var key = chatDayKey(isoOrDate);
        if (!key) return;
        var last = $box.children('.chat-day-sep').last();
        if (last.length && String(last.attr('data-day')) === key) return;
        $box.append(
          $('<div>').addClass('chat-day-sep').attr('data-day', key).append(
            $('<span>').text(formatChatDayLabel(isoOrDate))
          )
        );
      }

      function buildChatNode(message, isAdmin, attachments, meta) {
        meta = meta || {};
        var type = meta.type || null;
        var metaPayload = meta.payload || null;
        if (metaPayload && typeof metaPayload === 'string') {
          try { metaPayload = JSON.parse(metaPayload); } catch (e) { metaPayload = null; }
        }

        var payload = null;
        if (type === 'product_share' || type === 'order_share') {
          payload = metaPayload || getSharedPayload(message);
          if (payload) payload.__shareType = (type === 'order_share') ? 'order' : 'product';
        } else if (!type || type === 'text' || type === 'attachment') {
          // No explicit type (older message) — fall back to prefix-sniffing.
          payload = getSharedPayload(message);
        }

        var cls = isAdmin ? 'chat_msg_item chat_msg_item_admin' : 'chat_msg_item chat_msg_item_user';
        var node = $('<span>').addClass(cls);
        if (meta.replyId) {
          node.attr('data-reply-id', meta.replyId);
        }
        if (meta.createdAt) {
          node.attr('data-created-at', meta.createdAt);
        }

        var attBlock = buildAttachmentBlock(attachments);
        if (attBlock) {
          node.append(attBlock);
        }

        if (type === 'location' && metaPayload) {
          if (isAdmin) {
            agent_avatar.clone().prependTo(node);
          }
          var locWrap = $('<div>').addClass('chat-shared-product-wrap');
          var locCard = $('<div>').addClass('chat-shared-product chat-shared-location');
          $('<div>').addClass('chat-shared-share-icon').html('<i class="fa fa-map-marker"></i>').appendTo(locCard);
          var locBody = $('<div>').addClass('chat-shared-product-body').appendTo(locCard);
          $('<div>').addClass('chat-shared-product-title').text(metaPayload.label || LC_I18N.location).appendTo(locBody);
          var mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' +
            encodeURIComponent((metaPayload.lat || '') + ',' + (metaPayload.lng || ''));
          $('<a>').addClass('chat-shared-product-link').attr('href', mapsUrl).attr('target', '_blank').attr('rel', 'noopener')
            .text(LC_I18N.open_in_maps).appendTo(locBody);
          locWrap.append(locCard);
          node.append(locWrap);
        } else if (type === 'contact' && metaPayload) {
          if (isAdmin) {
            agent_avatar.clone().prependTo(node);
          }
          var conWrap = $('<div>').addClass('chat-shared-product-wrap');
          var conCard = $('<div>').addClass('chat-shared-product chat-shared-contact');
          $('<div>').addClass('chat-shared-share-icon').html('<i class="fa fa-user"></i>').appendTo(conCard);
          var conBody = $('<div>').addClass('chat-shared-product-body').appendTo(conCard);
          $('<div>').addClass('chat-shared-product-title').text(metaPayload.name || 'Contact').appendTo(conBody);
          if (metaPayload.phone) {
            $('<div>').addClass('chat-shared-product-price').text(metaPayload.phone).appendTo(conBody);
            $('<a>').addClass('chat-shared-product-link').attr('href', 'tel:' + metaPayload.phone).text(LC_I18N.call).appendTo(conBody);
          }
          conWrap.append(conCard);
          node.append(conWrap);
        } else if (!payload) {
          var text = (message || '').trim();
          if (text && text !== '[attachment]') {
            node.append($('<span>').addClass('chat-msg-text').html(linkifyText(text)));
          }
        } else {
          if (isAdmin) {
            agent_avatar.clone().prependTo(node);
          }

          var wrap = $('<div>').addClass('chat-shared-product-wrap');
          var card = $('<div>').addClass('chat-shared-product' + (payload.__shareType === 'order' ? ' chat-shared-order' : ''));
          if (payload.image) {
            $('<img>').addClass('chat-shared-product-img').attr('src', payload.image || '').attr('alt', payload.title || '').attr('loading', 'lazy').appendTo(card);
          }
          var body = $('<div>').addClass('chat-shared-product-body').appendTo(card);
          $('<div>').addClass('chat-shared-product-title').text(payload.title || '').appendTo(body);
          var subtitle = payload.__shareType === 'order'
            ? ((payload.total || '') + (payload.status ? ' · ' + payload.status : ''))
            : (payload.price || '');
          $('<div>').addClass('chat-shared-product-price').text(subtitle).appendTo(body);
          $('<a>').addClass('chat-shared-product-link').attr('href', payload.url || '#').attr('target', '_blank')
            .text(payload.__shareType === 'order' ? LC_I18N.view_order : LC_I18N.view).appendTo(body);
          wrap.append(card);
          node.append(wrap);
        }

        var clock = meta.time || formatChatClock(meta.createdAt || new Date().toISOString());
        node.append($('<time>').addClass('chat-msg-time').attr('datetime', meta.createdAt || '').text(clock));

        return node;
      }

      // Expose for websocket callback script block below.
      window.buildChatNode = buildChatNode;
      window.ensureStorefrontDaySep = ensureStorefrontDaySep;
      window.formatChatClock = formatChatClock;

      if (shareStorageKey && window.sessionStorage.getItem(shareStorageKey) === '1') {
        $('.chat-product-share').not('.chat-order-share').hide();
      }
      if (shareOrderStorageKey && window.sessionStorage.getItem(shareOrderStorageKey) === '1') {
        $('.chat-order-share').hide();
      }

      // When send button clicked
      $("#fchat_send").on('click', function() {
        sendTheMessage();
      });

      (function bindChatFileInput() {
        var label = document.getElementById('chat_composer_attach');
        var fi = document.getElementById('chatBoxFile');
        if (label) {
          label.addEventListener('mousedown', function() {
            var input = document.getElementById('chatBoxFile');
            if (input) {
              input.value = '';
            }
          }, true);
        }
        if (fi) {
          fi.addEventListener('change', function() {
            refreshAttachmentPreviewStrip();
          }, false);
        }
      })();

      $('#fchat_remove_attachment').on('click', function(e) {
        e.preventDefault();
        clearAttachmentPreview();
      });

      // ---- Attachment menu (WhatsApp-style "+" popover) ----
      (function bindAttachMenu() {
        var toggleBtn = document.getElementById('chat_attach_toggle');
        var menu = document.getElementById('chat_attach_menu');
        if (!toggleBtn || !menu) return;

        function closeMenu() {
          menu.hidden = true;
          toggleBtn.setAttribute('aria-expanded', 'false');
        }
        function openMenu() {
          menu.hidden = false;
          toggleBtn.setAttribute('aria-expanded', 'true');
        }
        toggleBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          if (menu.hidden) { openMenu(); } else { closeMenu(); }
        });
        document.addEventListener('click', function(e) {
          if (!menu.hidden && !menu.contains(e.target) && e.target !== toggleBtn) {
            closeMenu();
          }
        });

        var mediaLabel = document.getElementById('chat_composer_attach');
        if (mediaLabel) {
          mediaLabel.addEventListener('click', function() { closeMenu(); });
        }

        var productBtn = document.getElementById('chat_attach_product');
        if (productBtn) {
          productBtn.addEventListener('click', function() {
            closeMenu();
            openPickerModal('product');
          });
        }

        var orderBtn = document.getElementById('chat_attach_order');
        if (orderBtn) {
          orderBtn.addEventListener('click', function() {
            closeMenu();
            openPickerModal('order');
          });
        }
      })();

      function appendChatError(text) {
        var response = $('<p>').addClass('text-danger').text(text);
        $('#chat_conversation').append(response);
        updateScroll();
      }

      // ---- Product / Order picker modal ----
      var pickerFetchToken = 0;
      var pickerSearchDebounce = null;
      function openPickerModal(kind) {
        var modal = document.getElementById('chat_picker_modal');
        if (!modal) return;
        $('#chat_picker_title').text(kind === 'order' ? LC_I18N.share_an_order : LC_I18N.share_a_product);
        $('#chat_picker_search').val('').show().attr('placeholder', kind === 'order' ? LC_I18N.search_order_number : LC_I18N.search);
        modal.setAttribute('data-picker-kind', kind);
        modal.hidden = false;
        loadPickerItems(kind, '');
      }
      function closePickerModal() {
        var modal = document.getElementById('chat_picker_modal');
        if (modal) modal.hidden = true;
      }
      $(document).on('click', '#chat_picker_modal', function(e) {
        if (e.target.id === 'chat_picker_modal' || $(e.target).is('[data-modal-close]')) {
          closePickerModal();
        }
      });
      $(document).on('input', '#chat_picker_search', function() {
        var modal = document.getElementById('chat_picker_modal');
        var kind = modal ? modal.getAttribute('data-picker-kind') : 'product';
        var term = $.trim($(this).val());
        if (pickerSearchDebounce) clearTimeout(pickerSearchDebounce);
        pickerSearchDebounce = setTimeout(function() { loadPickerItems(kind, term); }, 300);
      });

      function loadPickerItems(kind, term) {
        var myToken = ++pickerFetchToken;
        var $list = $('#chat_picker_list');
        $list.html('<p class="chat-picker-empty">{{ trans('theme.livechat.loading') }}</p>');
        var url = (kind === 'order'
          ? "{{ route('chat.orders', $shop->id) }}"
          : "{{ route('chat.products', $shop->id) }}") + (term ? ('?q=' + encodeURIComponent(term)) : '');
        $.ajax({
          url: url,
          method: 'GET',
          beforeSend: setChatAjaxHeaders,
          success: function(res) {
            if (myToken !== pickerFetchToken) return;
            var items = (res && res.data) || [];
            if (!items.length) {
              $list.html($('<p class="chat-picker-empty">').text(LC_I18N.nothing_to_show));
              return;
            }
            $list.empty();
            items.forEach(function(item) {
              var card = $('<button type="button">').addClass('chat-picker-item');
              if (item.image) {
                $('<img>').addClass('chat-picker-item-img').attr('src', item.image).attr('alt', '').appendTo(card);
              }
              var body = $('<div>').addClass('chat-picker-item-body').appendTo(card);
              $('<div>').addClass('chat-picker-item-title').text(item.title || item.order_number || '').appendTo(body);
              var subtitle = kind === 'order'
                ? ((item.total || '') + (item.status ? ' · ' + item.status : ''))
                : (item.price || '');
              $('<div>').addClass('chat-picker-item-sub').text(subtitle).appendTo(body);
              card.on('click', function() {
                closePickerModal();
                if (kind === 'order') {
                  sendTheMessage(orderSharePrefix + JSON.stringify(item), { type: 'order_share', payload: item });
                } else {
                  sendTheMessage(sharePrefix + JSON.stringify(item), { type: 'product_share', payload: item });
                }
              });
              $list.append(card);
            });
          },
          error: function() {
            if (myToken !== pickerFetchToken) return;
            $list.html($('<p class="chat-picker-empty">').text(LC_I18N.could_not_load));
          },
        });
      }

      // Send on Enter only inside chat box (Shift+Enter for new line)
      $("#chatBoxMsg").on('keydown', function(event) {
        if (event.key === 'Enter') {
          event.preventDefault();
          sendTheMessage();
        }
      });

      $('#chatbox').click(function() {
        toggleFchat();
      });

      $('#sf_livechat_close').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if ($('#chat-window').hasClass('is-visible')) {
          toggleFchat();
        }
      });

      // Open chat from product/seller page buttons without toggling closed.
      window.openStorefrontLiveChat = function() {
        if (!$('#chat-window').hasClass('is-visible')) {
          toggleFchat();
        } else if (typeof updateScroll === 'function') {
          updateScroll();
        }
      };

      $(document).off('click.sfOpenLiveChat', '.sf-open-livechat').on('click.sfOpenLiveChat', '.sf-open-livechat', function(e) {
        e.preventDefault();
        if (typeof window.openStorefrontLiveChat === 'function') {
          window.openStorefrontLiveChat();
        } else {
          $('#chatbox').trigger('click');
        }
      });

      function hideProductSharePreview() {
        $('.chat-product-share').not('.chat-order-share').slideUp(120);
        if (shareStorageKey) {
          window.sessionStorage.setItem(shareStorageKey, '1');
        }
      }

      function hideOrderSharePreview() {
        $('.chat-order-share').slideUp(120);
        if (shareOrderStorageKey) {
          window.sessionStorage.setItem(shareOrderStorageKey, '1');
        }
      }

      $('#fchat_dismiss_product_share').on('click', function(e) {
        e.preventDefault();
        hideProductSharePreview();
      });

      $('#fchat_dismiss_order_share').on('click', function(e) {
        e.preventDefault();
        hideOrderSharePreview();
      });

      $("#fchat_share_product").on('click', function() {
        if (!shareProductMessage) return;
        sendTheMessage(shareProductMessage, { type: 'product_share', payload: shareProductPayload });
        hideProductSharePreview();
      });

      $("#fchat_share_order").on('click', function() {
        if (!shareOrderMessage) return;
        sendTheMessage(shareOrderMessage, { type: 'order_share', payload: shareOrderPayload });
        hideOrderSharePreview();
      });

      if (shareStorageKey && window.sessionStorage.getItem(shareStorageKey) === '1') {
        hideProductSharePreview();
      }
      if (shareOrderStorageKey && window.sessionStorage.getItem(shareOrderStorageKey) === '1') {
        hideOrderSharePreview();
      }

      function setChatAjaxHeaders(xhr) {
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        var csrf = $('meta[name="csrf-token"]').attr('content');
        if (csrf) {
          xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
        }
      }

      function chatSendHeadersForFetch() {
        var h = {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        };
        var csrf = $('meta[name="csrf-token"]').attr('content');
        if (csrf) {
          h['X-CSRF-TOKEN'] = csrf;
        }
        return h;
      }

      function handleChatSendComplete(httpStatus, pendingNode, xhrOrBody) {
        isSendingMessage = false;
        $("#fchat_send").removeClass('hidden');

        var response = '';
        var shouldAppendResponse = true;
        var body = null;
        try {
          if (xhrOrBody && xhrOrBody.responseText) {
            body = JSON.parse(xhrOrBody.responseText);
          } else if (xhrOrBody && typeof xhrOrBody === 'object' && !xhrOrBody.statusText) {
            body = xhrOrBody;
          }
        } catch (e) {
          body = null;
        }

        switch (httpStatus) {
          case 200:
            clearAttachmentPreview();
            // First message: remove welcome / hint so only the real thread shows.
            $('#chat_conversation .chat_welcome_bubble, #chat_conversation .chat_start_hint').remove();
            if (pendingNode && pendingNode.length) {
              pendingNode.removeAttr('data-pending');
              if (body && body.reply_id) {
                pendingNode.attr('data-reply-id', body.reply_id);
              }
              if (body && body.created_at) {
                pendingNode.attr('data-created-at', body.created_at);
              }
              if (body && (body.time || body.created_at)) {
                pendingNode.find('.chat-msg-time').text(body.time || formatChatClock(body.created_at));
              }
            }
            shouldAppendResponse = false;
            // Do not soft-refresh via loadOldChat — that was wiping first messages.
            break;

          case 401:
            clearAttachmentPreview();
            $("#chat_conversation").html("");
            response = $('<p>').addClass('text-danger').text("{!! trans('theme.login_to_chat') !!}");
            $('<br/><br/>').prependTo(response);
            $('<a>').attr('href', "javascript:void(0)").attr('data-toggle', "modal").attr('data-target', "#loginModal").addClass('btn btn-primary').text("{{ trans('theme.button.login') }}").appendTo(response);
            break;

          case 403:
          case 419:
            clearAttachmentPreview();
            $("#chat_conversation").html("");
            response = $('<p>').addClass('text-danger').text("{!! trans('theme.session_expired') !!}");
            $('<br/><br/>').prependTo(response);
            $('<a>').attr('href', "javascript:void(0)").attr('data-toggle', "modal").attr('data-target', "#loginModal").addClass('btn btn-primary').text("{{ trans('theme.button.login') }}").appendTo(response);
            break;

          case 404:
            clearAttachmentPreview();
            response = $('<p>').addClass('text-danger').text("{!! trans('theme.shop_not_found') !!}");
            $('<br/><br/>').prependTo(response);
            $('<a>').attr('href', "/").addClass('btn btn-primary').text("{{ trans('theme.button.shop_now') }}").appendTo(response);
            break;

          case 405:
            pendingNode.remove();
            clearAttachmentPreview();
            response = $('<p>').addClass('text-danger').text(LC_I18N.request_blocked);
            break;

          default:
            pendingNode.remove();
            response = $('<p>').addClass('text-danger').text(
              httpStatus === 0
                ? LC_I18N.network_error
                : "{!! trans('theme.notify.failed') !!}"
            );
            $('<br/><br/>').prependTo(response);
        }

        if (shouldAppendResponse) {
          $("#chat_conversation").append(response);
          updateScroll();
        }
      }

      // Send the message. `extra` (optional) = { type, payload } for
      // location/contact/product_share/order_share messages.
      function sendTheMessage(customMessage, extra) {
        if (isSendingMessage) return;
        extra = extra || {};

        var fileInput = document.getElementById('chatBoxFile');
        var hasFile = fileInput && fileInput.files && fileInput.files.length;
        var msg = (typeof customMessage === 'string')
          ? $.trim(customMessage)
          : $.trim($("#chatBoxMsg").val());

        if (msg === '' && !hasFile) return;

        var fdFile = hasFile && fileInput.files && fileInput.files.length ? fileInput.files[0] : null;
        var nowIso = new Date().toISOString();

        // Optimistic UI: show message immediately, persist in background.
        ensureStorefrontDaySep(nowIso);
        var pendingNode = buildChatNode(msg || (hasFile ? "{{ trans('theme.attachment') }}" : ''), false, null, {
          createdAt: nowIso,
          time: formatChatClock(nowIso),
          type: extra.type,
          payload: extra.payload,
        }).attr('data-pending', '1');
        $("#chat_conversation").append(pendingNode);
        updateScroll();
        $("#chatBoxMsg").val('');

        if (hasFile && fdFile) {
          clearAttachmentPreview();
        }

        isSendingMessage = true;
        $("#fchat_send").addClass('hidden');

        var chatPostUrl = "{{ route('chat.start') }}";

        if (hasFile && fdFile) {
          var fd = new FormData();
          fd.append('message', msg);
          fd.append('shop_slug', "{{ $shop->slug }}");
          fd.append('_token', "{{ csrf_token() }}");
          fd.append('photo', fdFile);
          if (extra.type) { fd.append('type', extra.type); }
          if (extra.payload) { fd.append('payload', JSON.stringify(extra.payload)); }

          if (typeof window.fetch === 'function') {
            window.fetch(chatPostUrl, {
              method: 'POST',
              body: fd,
              credentials: 'same-origin',
              headers: chatSendHeadersForFetch(),
            }).then(function(res) {
              return res.json().then(function(json) {
                handleChatSendComplete(res.status, pendingNode, json);
              }).catch(function() {
                handleChatSendComplete(res.status, pendingNode, null);
              });
            }).catch(function() {
              handleChatSendComplete(0, pendingNode, null);
            });
          } else {
            $.ajax({
              url: chatPostUrl,
              type: 'POST',
              data: fd,
              processData: false,
              contentType: false,
              beforeSend: setChatAjaxHeaders,
              complete: function(xhr) {
                handleChatSendComplete(xhr.status, pendingNode, xhr);
              },
            });
          }
          return;
        }

        var ajaxData = {
          'message': msg,
          'shop_slug': "{{ $shop->slug }}",
          '_token': "{{ csrf_token() }}",
        };
        if (extra.type) { ajaxData.type = extra.type; }
        if (extra.payload) { ajaxData.payload = JSON.stringify(extra.payload); }

        $.ajax({
          url: chatPostUrl,
          type: 'POST',
          data: ajaxData,
          beforeSend: setChatAjaxHeaders,
          complete: function(xhr) {
            handleChatSendComplete(xhr.status, pendingNode, xhr);
          },
        });
      }

      //Toggle chat and links
      function toggleFchat() {
        $('.chat-icon').toggleClass('fa-comment');
        $('.chat-icon').toggleClass('fa-times');
        $('.chat-icon').toggleClass('is-active');
        $('.chat-icon').toggleClass('is-visible');
        $('#chatbox').toggleClass('is-float');
        $('.chat').toggleClass('is-visible');
        $('.fchat').toggleClass('is-visible');

        if ($("#chat-window").hasClass('is-visible')) {
          loadOldChat();
          if (! chatPoller) {
            chatPoller = setInterval(function() {
              // Fallback only when websocket is not connected.
              if (!window.socketConnected) {
                loadOldChat();
              }
            }, 5000);
          }
        } else if (chatPoller) {
          clearInterval(chatPoller);
          chatPoller = null;
        }
      }

      // Load conversation history — empty thread = first-time welcome (not an error).
      function loadOldChat() {
        var loadTimedOut = false;
        var loadTimer = setTimeout(function() {
          loadTimedOut = true;
          showChatLoadError(0, null);
        }, 12000);

        var historyUrl = @json(route('chat.conversation', $shop->slug ?: $shop->id));

        function showWelcomePrompt(welcomeText, hintText) {
          var $box = $("#chat_conversation");
          $box.html('');
          var welcome = welcomeText || @json(trans('theme.chat_welcome'));
          var hint = hintText || @json(trans('theme.chat_start_hint'));
          var $bubble = $('<span>').addClass('chat_msg_item chat_msg_item_admin chat_welcome_bubble').text(welcome);
          agent_avatar.clone().prependTo($bubble);
          $box.append($bubble);
          if (hint) {
            $box.append($('<p>').addClass('chat_start_hint text-muted').css({
              'text-align': 'center',
              'font-size': '12px',
              'margin': '10px 12px 0',
              'opacity': '0.85'
            }).text(hint));
          }
          updateScroll();
        }

        function showChatLoadError(status, payload) {
          var $box = $('#chat_conversation');
          if ($box.find('.chat_login_prompt').length) return;
          if ($box.find('.chat_msg_item:not(.chat_welcome_bubble), .chat-day-sep').length) return;

          if (payload && (payload.status === 'empty' || payload.is_new === true)) {
            showWelcomePrompt(payload.welcome, payload.hint);
            return;
          }

          var msg = @json(trans('theme.chat_load_failed'));
          var showRetry = true;

          if (status === 404) {
            msg = (payload && payload.message) ? String(payload.message) : @json(trans('theme.chat_not_found'));
            showRetry = false;
          } else if (status === 401 || status === 403 || status === 419) {
            msg = @json(trans('theme.session_expired'));
          } else if (status === 405) {
            msg = LC_I18N.request_blocked;
          } else if (payload && payload.message) {
            msg = String(payload.message);
          }

          $box.html('');
          var $err = $('<div>').addClass('chat-load-error');
          $('<p>').text(msg).appendTo($err);
          if (showRetry) {
            $('<button type="button">').addClass('chat-load-retry').text(@json(trans('theme.chat_retry')))
              .on('click', function() {
                $box.html('<p class="chat_connecting text-primary">' + @json(trans('theme.connecting')) + '</p>');
                loadOldChat();
              }).appendTo($err);
          } else {
            $('<button type="button">').addClass('chat-load-retry').text(@json(trans('theme.chat_welcome')))
              .on('click', function() { showWelcomePrompt(); }).appendTo($err);
          }
          $box.append($err);
        }

        function renderConversation(result) {
          $("#chat_conversation").html('');
          var replies = result.replies || [];
          if (!replies.length) {
            var legacyAt = result.created_at || new Date().toISOString();
            ensureStorefrontDaySep(legacyAt);
            $("#chat_conversation").append(buildChatNode(result.message, false, result.attachments, {
              createdAt: legacyAt,
              time: formatChatClock(legacyAt),
              type: result.type,
              payload: result.payload,
            }));
          } else {
            var lastDay = null;
            replies.forEach(function(reply) {
              var at = reply.created_at || result.updated_at || new Date().toISOString();
              var day = chatDayKey(at);
              if (day && day !== lastDay) {
                lastDay = day;
                $("#chat_conversation").append(
                  $('<div>').addClass('chat-day-sep').attr('data-day', day).append(
                    $('<span>').text(formatChatDayLabel(at))
                  )
                );
              }
              $("#chat_conversation").append(buildChatNode(reply.reply, !!reply.user_id, reply.attachments, {
                replyId: reply.id,
                createdAt: at,
                time: formatChatClock(at),
                type: reply.type,
                payload: reply.payload,
              }));
            });
          }
          updateScroll();
        }

        $.ajax({
          url: historyUrl,
          dataType: 'json',
          cache: false,
          beforeSend: setChatAjaxHeaders,
          success: function(result) {
            clearTimeout(loadTimer);
            if (loadTimedOut) return;

            if (!result || result.status === 'empty' || result.is_new === true) {
              if ($('#chat_conversation').find('.chat_msg_item, .chat-day-sep').length) return;
              showWelcomePrompt(result && result.welcome, result && result.hint);
              return;
            }
            if (result.code === 'chat_not_found') {
              showChatLoadError(404, result);
              return;
            }
            var hasHistory = result.id || (result.replies && result.replies.length) || result.message;
            if (!hasHistory) {
              if ($('#chat_conversation').find('.chat_msg_item, .chat-day-sep').length) return;
              showWelcomePrompt();
              return;
            }
            renderConversation(result);
          },
          error: function(xhr) {
            clearTimeout(loadTimer);
            var payload = null;
            try {
              payload = xhr.responseJSON || (xhr.responseText ? JSON.parse(xhr.responseText) : null);
            } catch (e) { payload = null; }
            if ($('#chat_conversation').find('.chat_msg_item, .chat-day-sep').length) return;
            showChatLoadError(xhr && xhr.status ? xhr.status : 0, payload);
          }
        });
      }
    });
  }(window.jQuery, window, document));
</script>

@if (Auth::guard('customer')->check())
  <script type="text/javascript">
    "use strict";
    (function($, window, document) {
      $(document).ready(function() {
        var room = '{{ get_chat_room_name($shop->id . Auth::guard('customer')->user()->id) }}';
        var wsScheme = '{{ config('chat_socket.scheme') }}';
        var wsHost = '{{ config('chat_socket.client_host') }}';
        var wsPort = '{{ (int) config('chat_socket.port') }}';
        var wsPath = '{{ trim((string) config('chat_socket.client_path', '')) }}';
        (function() {
          wsScheme = String(wsScheme || 'ws').replace(/:$/, '');
          wsHost = String(wsHost || '127.0.0.1').trim();
          wsPath = String(wsPath || '').trim();
          if (wsHost === '0.0.0.0' || wsHost.indexOf('0.0.0.0:') === 0) {
            wsHost = '127.0.0.1' + (wsHost.indexOf(':') > -1 ? wsHost.substring(wsHost.indexOf(':')) : '');
          }
          if (wsPath && wsPath.charAt(0) !== '/') {
            wsPath = '/' + wsPath;
          }
          var hostHasPort = /:\d+$/.test(wsHost);
          window.__chatWsUrl = wsScheme + '://' + wsHost;
          if (wsPath) {
            window.__chatWsUrl += wsPath;
          } else if (!hostHasPort && wsPort && !(wsScheme === 'ws' && String(wsPort) === '80') && !(wsScheme === 'wss' && String(wsPort) === '443')) {
            window.__chatWsUrl += ':' + wsPort;
          }
        })();
        var wsUrl = window.__chatWsUrl;
        var socket = null;

        // Cross-tab sync: every open tab already gets its own WebSocket
        // subscribed to this same room (the server broadcasts to every
        // subscriber), so multiple tabs already update live independently.
        // BroadcastChannel closes the one real gap — if THIS tab's socket is
        // mid-reconnect and misses a message that another tab's socket did
        // catch, that other tab relays it here so nothing is ever missed
        // regardless of this tab's own connection state.
        var chatBroadcast = null;
        try {
          if (typeof BroadcastChannel !== 'undefined') {
            chatBroadcast = new BroadcastChannel('zcart_livechat_' + room);
          }
        } catch (e) {
          chatBroadcast = null;
        }

        function handleIncomingChatMessage(result, fromBroadcast) {
          var senderType = result.sender_type || '';

          // Dedup when WS replay / multi-tab / cross-tab broadcast delivers
          // the same reply again.
          if (result.reply_id &&
              $('#chat_conversation [data-reply-id="' + result.reply_id + '"]').length) {
            return;
          }

          if (senderType !== 'merchant') {
            return;
          }

          var renderer = (typeof window.buildChatNode === 'function')
            ? window.buildChatNode
            : function(message) {
                return $('<span>').addClass('chat_msg_item chat_msg_item_admin').text(message || '');
              };

          if (typeof window.ensureStorefrontDaySep === 'function') {
            window.ensureStorefrontDaySep(result.created_at || new Date().toISOString());
          }

          var response = renderer(result.text || '', true, result.attachments || [], {
            replyId: result.reply_id,
            createdAt: result.created_at,
            time: result.time || (typeof window.formatChatClock === 'function' ? window.formatChatClock(result.created_at) : ''),
            type: result.type,
            payload: result.payload,
          });
          if (result.reply_id && response && response.attr) {
            response.attr('data-reply-id', result.reply_id);
          }
          $("#chat_conversation").append(response);
          if (typeof window.updateScroll === 'function') {
            window.updateScroll();
          } else {
            var objDiv = document.getElementById("chat_conversation");
            if (objDiv) objDiv.scrollTop = objDiv.scrollHeight;
          }

          if (!fromBroadcast && chatBroadcast) {
            try { chatBroadcast.postMessage(result); } catch (e) {}
          }
        }

        if (chatBroadcast) {
          chatBroadcast.onmessage = function(ev) {
            handleIncomingChatMessage(ev.data, true);
          };
        }

        function connectSocket() {
          try {
            socket = new WebSocket(wsUrl);
          } catch (e) {
            return;
          }

          socket.onopen = function() {
            window.socketConnected = true;
            socket.send(JSON.stringify({
              action: 'subscribe',
              room: room
            }));
          };

          socket.onmessage = function(event) {
            var parsed;
            try {
              parsed = JSON.parse(event.data);
            } catch (e) {
              return;
            }

            if (!parsed || parsed.event !== 'chat.message' || !parsed.data) {
              return;
            }

            handleIncomingChatMessage(parsed.data, false);
          };

          socket.onclose = function() {
            window.socketConnected = false;
            setTimeout(connectSocket, 1200);
          };
        }

        connectSocket();
      });
    }(window.jQuery, window, document));
  </script>
@endif

@include('liveChat::partials.livechat_styles')

