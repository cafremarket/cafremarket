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
        <button type="button" class="chat_header_close" id="sf_livechat_close" aria-label="Close chat">&times;</button>
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
          <div class="chat-product-share-title">{{ trans('theme.ask') ?? 'Ask about this product' }}</div>
          <div class="chat-product-share-card">
            <div class="chat-product-share-media">
              <img src="{{ get_storage_file_url(optional($product->image)->path, 'tiny_thumb') }}" alt="{{ $product->title }}">
            </div>
            <div class="chat-product-share-body">
              <div class="chat-product-share-name">{{ \Illuminate\Support\Str::limit($product->title, 34) }}</div>
              <div class="chat-product-share-price">{{ get_formated_currency($product->current_sale_price(), 2) }}</div>
            </div>
            <div class="chat-product-share-actions">
              <button id="fchat_share_product" class="chat-product-share-btn" type="button" aria-label="Share product details">
                Share
              </button>
              <button type="button" id="fchat_dismiss_product_share" class="chat-product-share-dismiss" aria-label="Dismiss product preview">&times;</button>
            </div>
          </div>
        </div>
      @endisset
      <div class="fchat_field chat-composer">
        <div class="chat-composer-inner">
          <div id="chat-attachment-preview" class="chat-attachment-preview" aria-live="polite" aria-hidden="true" style="display:none">
            <div class="chat-attachment-preview-inner">
              <span class="chat-attachment-preview-label">Attachment</span>
              <div class="chat-attachment-preview-row">
                <img class="chat-attachment-preview-img" alt="" width="44" height="44">
                <span class="chat-attachment-preview-icon" aria-hidden="true"><i class="fa fa-file-o"></i></span>
                <span class="chat-attachment-preview-name"></span>
                <button type="button" id="fchat_remove_attachment" class="chat-attachment-preview-remove" aria-label="Remove attachment">&times;</button>
              </div>
            </div>
          </div>
          <div class="chat-composer-row">
            <label id="chat_composer_attach" class="chat-composer-btn chat-composer-btn--attach" title="Attach file">
              <input type="file" id="chatBoxFile" name="photo" class="chat-composer-file-input" accept="image/*,.pdf,.doc,.docx" tabindex="-1">
              <span class="chat-composer-btn-icon" aria-hidden="true"><i class="fa fa-paperclip"></i></span>
              <span class="chat-sr-only">Attach file</span>
            </label>
            <input id="chatBoxMsg" name="chat_message" type="text" placeholder="Send a message" class="chat_field chat_message chat-composer-msg" aria-label="Chat message input" autocomplete="off">
            <button type="button" id="fchat_send" class="chat-composer-btn chat-composer-btn--send" aria-label="Send message">
              <span class="chat-composer-btn-icon" aria-hidden="true"><i class="fa fa-paper-plane"></i></span>
            </button>
          </div>
        </div>
      </div>
    @endif
  </div>

  <a id="chatbox" class="fchat sf-livechat-fab" aria-label="Open chat">
    <i class="chat-icon fas fa-comment"></i>
  </a>
</div>

<script type="text/javascript">
  "use strict";
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
      @php
        $chatSharePayload = null;
        $chatShareStorageKey = null;
        if (isset($product)) {
            $chatSharePayload = [
                'title' => $product->title,
                'price' => get_formated_currency($product->current_sale_price(), 2),
                'url' => storefront_product_url($product),
                'image' => get_storage_file_url(optional($product->image)->path, 'tiny_thumb'),
            ];
            $chatShareStorageKey = 'chat_shared_product_'.$product->id;
        }
      @endphp
      var shareProductPayload = @json($chatSharePayload);
      var shareStorageKey = @json($chatShareStorageKey);
      var shareProductMessage = shareProductPayload ? (sharePrefix + JSON.stringify(shareProductPayload)) : null;

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
        var idx = raw.indexOf(sharePrefix);
        if (idx === -1) return null;
        var rest = raw.substring(idx + sharePrefix.length).trim();
        try {
          return JSON.parse(rest);
        } catch (e) {
          var start = rest.indexOf('{');
          var end = rest.lastIndexOf('}');
          if (start === -1 || end === -1 || end <= start) return null;
          try {
            return JSON.parse(rest.substring(start, end + 1));
          } catch (e2) {
            return null;
          }
        }
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
        var payload = getSharedPayload(message);
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

        if (!payload) {
          var text = (message || '').trim();
          if (text && text !== '[attachment]') {
            node.append($('<span>').addClass('chat-msg-text').text(text));
          }
        } else {
          if (isAdmin) {
            agent_avatar.clone().prependTo(node);
          }

          var wrap = $('<div>').addClass('chat-shared-product-wrap');
          var card = $('<div>').addClass('chat-shared-product');
          $('<img>').addClass('chat-shared-product-img').attr('src', payload.image || '').attr('alt', payload.title || 'product').attr('loading', 'lazy').appendTo(card);
          var body = $('<div>').addClass('chat-shared-product-body').appendTo(card);
          $('<div>').addClass('chat-shared-product-title').text(payload.title || '').appendTo(body);
          $('<div>').addClass('chat-shared-product-price').text(payload.price || '').appendTo(body);
          $('<a>').addClass('chat-shared-product-link').attr('href', payload.url || '#').attr('target', '_blank').text('View').appendTo(body);
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
        $('.chat-product-share').hide();
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
        $('.chat-product-share').slideUp(120);
        if (shareStorageKey) {
          window.sessionStorage.setItem(shareStorageKey, '1');
        }
      }

      $('#fchat_dismiss_product_share').on('click', function(e) {
        e.preventDefault();
        hideProductSharePreview();
      });

      $("#fchat_share_product").on('click', function() {
        if (!shareProductMessage) return;
        sendTheMessage(shareProductMessage);
        hideProductSharePreview();
      });

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
            // Soft refresh once so history stays consistent — no double optimistic+history bubble.
            setTimeout(loadOldChat, 350);
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
            response = $('<p>').addClass('text-danger').text('Request blocked (AJAX required). Please refresh the page.');
            break;

          default:
            pendingNode.remove();
            response = $('<p>').addClass('text-danger').text(
              httpStatus === 0
                ? 'Network error. Check your connection and try again.'
                : "{!! trans('theme.notify.failed') !!}"
            );
            $('<br/><br/>').prependTo(response);
        }

        if (shouldAppendResponse) {
          $("#chat_conversation").append(response);
          updateScroll();
        }
      }

      // Send the message
      function sendTheMessage(customMessage) {
        if (isSendingMessage) return;

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

        $.ajax({
          url: chatPostUrl,
          type: 'POST',
          data: {
            'message': msg,
            'shop_slug': "{{ $shop->slug }}",
            '_token': "{{ csrf_token() }}",
          },
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

      //Load Old Chats
      function loadOldChat() {
        $.ajax({
          url: "{{ route('chat.conversation', $shop->id) }}",
          beforeSend: setChatAjaxHeaders,
          success: function(result) {
            $("#chat_conversation").html('');

            if (result) {
              var replies = result.replies || [];
              // conversation.message is an inbox preview (last text) — never render it as a bubble when replies exist.
              if (!replies.length) {
                var legacyAt = result.created_at || new Date().toISOString();
                ensureStorefrontDaySep(legacyAt);
                $("#chat_conversation").append(buildChatNode(result.message, false, result.attachments, {
                  createdAt: legacyAt,
                  time: formatChatClock(legacyAt),
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
                  }));
                });
              }
            } else {
              var response = $('<span>').addClass('chat_msg_item chat_msg_item_admin').text("{!! trans('theme.chat_welcome') !!}");
              agent_avatar.prependTo(response);
              $("#chat_conversation").append(response);
            }

            updateScroll();
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

            var result = parsed.data;
            var senderType = result.sender_type || '';

            // Dedup when WS replay / multi-tab delivers the same reply again.
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

