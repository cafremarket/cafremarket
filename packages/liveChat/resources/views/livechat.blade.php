<div id="zcart_chat">
  <div id="chat-window" class="chat">
    <div class="chat_header">
      <div class="chat_option">
        <div class="header_img">
          <img src="{{ get_storage_file_url(optional($shop->image)->path, 'thumbnail') }}" />
        </div>
        <span id="chat_head">{{ $shop->name }}</span> <br>
        <span class="agent">{{ $agent->getName() }}</span> <span class="online">({{ $agent_status }})</span>
      </div>
    </div>

    <div id="chat_conversation" class="chat_converse">
      {{-- <a id="chat_second_screen" class="fchat"><i class="fas fa-arrow-right"></i></a> --}}
      @unless (Auth::guard('customer')->check())
        <p>
          {!! trans('theme.login_to_chat') !!}
          <a href="javascript:void(0)" class="btn btn-primary" data-toggle="modal" data-target="#loginModal">{{ trans('theme.button.login') }}</a>
        </p>
      @else
        <p class="text-primary">{!! trans('theme.connecting') !!}</p>
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
              <span class="chat-composer-btn-icon" aria-hidden="true"><i class="fa fa-paper-plane-o"></i></span>
            </button>
          </div>
        </div>
      </div>
    @endif
  </div>

  <a id="chatbox" class="fchat">
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
                'url' => route('show.product', $product->slug),
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

      function buildChatNode(message, isAdmin, attachments) {
        var payload = getSharedPayload(message);
        var cls = isAdmin ? 'chat_msg_item chat_msg_item_admin' : 'chat_msg_item chat_msg_item_user';
        var node = $('<span>').addClass(cls);

        var attBlock = buildAttachmentBlock(attachments);
        if (attBlock) {
          node.append(attBlock);
        }

        if (!payload) {
          var text = (message || '').trim();
          if (text && text !== '[attachment]') {
            node.append($('<span>').addClass('chat-msg-text').text(text));
          }
          return node;
        }

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

        return node;
      }

      // Expose for websocket callback script block below.
      window.buildChatNode = buildChatNode;

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

      function handleChatSendComplete(httpStatus, pendingNode) {
        isSendingMessage = false;
        $("#fchat_send").removeClass('hidden');

        var response = '';
        var shouldAppendResponse = true;

        switch (httpStatus) {
          case 200:
            clearAttachmentPreview();
            pendingNode.removeAttr('data-pending');
            shouldAppendResponse = false;
            setTimeout(loadOldChat, 100);
            setTimeout(loadOldChat, 600);
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

        // Optimistic UI: show message immediately, persist in background.
        var pendingNode = buildChatNode(msg || (hasFile ? "{{ trans('theme.attachment') }}" : ''), false, null)
          .attr('data-pending', '1');
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
              handleChatSendComplete(res.status, pendingNode);
            }).catch(function() {
              handleChatSendComplete(0, pendingNode);
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
                handleChatSendComplete(xhr.status, pendingNode);
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
            handleChatSendComplete(xhr.status, pendingNode);
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
              var convMsg = String(result.message != null ? result.message : '').trim();
              var skipFirstReply = false;
              var firstAttachments = result.attachments;

              if (replies.length && !replies[0].user_id) {
                var r0 = String(replies[0].reply != null ? replies[0].reply : '').trim();
                if (r0 === convMsg) {
                  skipFirstReply = true;
                  if (replies[0].attachments && replies[0].attachments.length) {
                    firstAttachments = replies[0].attachments;
                  }
                }
              }

              $("#chat_conversation").append(buildChatNode(result.message, false, firstAttachments));

              replies.forEach(function(reply, idx) {
                if (skipFirstReply && idx === 0) return;
                if (reply.user_id) {
                  $("#chat_conversation").append(buildChatNode(reply.reply, true, reply.attachments));
                } else {
                  $("#chat_conversation").append(buildChatNode(reply.reply, false, reply.attachments));
                }
              });
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
        if (wsPath && wsPath.charAt(0) !== '/') {
          wsPath = '/' + wsPath;
        }
        var wsUrl = wsScheme + '://' + wsHost + (wsPath ? wsPath : (':' + wsPort));
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

            if (parsed.data.sender_type !== 'merchant') {
              return;
            }

            var renderer = (typeof window.buildChatNode === 'function')
              ? window.buildChatNode
              : function(message) {
                  return $('<span>').addClass('chat_msg_item chat_msg_item_admin').text(message || '');
                };

            var response = renderer(parsed.data.text || '', true, parsed.data.attachments || []);
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

<style type="text/css">
  #zcart_chat {
    bottom: 0;
    position: fixed;
    margin: 1em;
    right: 0;
    z-index: 998;
  }

  #zcart_chat #chat_conversation {
    display: block;
  }

  #zcart_chat .btn {
    display: block;
    margin: 10px auto;
  }

  #zcart_chat ul li {
    list-style: none;
  }

  #zcart_chat .fchat {
    display: block;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    text-align: center;
    color: #f0f0f0;
    margin: 25px auto 0;
    box-shadow: 0 0 4px rgba(0, 0, 0, .14), 0 4px 8px rgba(0, 0, 0, .28);
    cursor: pointer;
    -webkit-transition: all .1s ease-out;
    transition: all .1s ease-out;
    position: relative;
    z-index: 998;
    overflow: hidden;
    background: #42a5f5;
  }

  #zcart_chat .fchat>i {
    font-size: 2em;
    line-height: 55px;
    -webkit-transition: all .2s ease-out;
    -webkit-transition: all .2s ease-in-out;
    transition: all .2s ease-in-out;
  }

  #zcart_chat .fchat:not(:last-child) {
    width: 0;
    height: 0;
    margin: 20px auto 0;
    opacity: 0;
    visibility: hidden;
    line-height: 40px;
  }

  #zcart_chat .fchat:not(:last-child)>i {
    font-size: 1.4em;
    line-height: 40px;
  }

  #zcart_chat .fchat:not(:last-child).is-visible {
    width: 40px;
    height: 40px;
    margin: 15px auto 10;
    opacity: 1;
    visibility: visible;
  }

  #zcart_chat .fchat:nth-last-child(1) {
    -webkit-transition-delay: 25ms;
    transition-delay: 25ms;
  }

  #zcart_chat .fchat:not(:last-child):nth-last-child(2) {
    -webkit-transition-delay: 20ms;
    transition-delay: 20ms;
  }

  #zcart_chat .fchat:not(:last-child):nth-last-child(3) {
    -webkit-transition-delay: 40ms;
    transition-delay: 40ms;
  }

  #zcart_chat .fchat:not(:last-child):nth-last-child(4) {
    -webkit-transition-delay: 60ms;
    transition-delay: 60ms;
  }

  #zcart_chat .fchat:not(:last-child):nth-last-child(5) {
    -webkit-transition-delay: 80ms;
    transition-delay: 80ms;
  }

  #zcart_chat .fchat(:last-child):active,
  #zcart_chat .fchat(:last-child):focus,
  #zcart_chat .fchat(:last-child):hover {
    box-shadow: 0 0 6px rgba(0, 0, 0, .16), 0 6px 12px rgba(0, 0, 0, .32);
  }

  /*Chatbox*/
  #zcart_chat .chat {
    position: fixed;
    right: 85px;
    bottom: 20px;
    width: 400px;
    font-size: 12px;
    line-height: 22px;
    font-family: 'Roboto';
    font-weight: 500;
    -webkit-font-smoothing: antialiased;
    font-smoothing: antialiased;
    display: none;
    box-shadow: 1px 1px 100px 2px rgba(0, 0, 0, 0.22);
    border-radius: 4px;
    -webkit-transition: all .2s ease-out;
    -webkit-transition: all .2s ease-in-out;
    transition: all .2s ease-in-out;
  }

  #zcart_chat .chat_header {
    /* margin: 10px; */
    font-size: 13px;
    font-family: 'Roboto';
    font-weight: 500;
    color: #f3f3f3;
    height: 55px;
    background: #42a5f5;
    border-top-left-radius: 4px;
    border-top-right-radius: 4px;
    padding-top: 8px;
  }

  #zcart_chat .chat_header2 {
    border-top-left-radius: 0px;
    border-top-right-radius: 0px;
  }

  #zcart_chat .chat_header .span {
    float: right;
  }

  #zcart_chat .chat.is-visible {
    display: block;
    -webkit-animation: zoomIn .2s cubic-bezier(.42, 0, .58, 1);
    animation: zoomIn .2s cubic-bezier(.42, 0, .58, 1);
  }

  #zcart_chat .is-hide {
    opacity: 0
  }

  #zcart_chat .chat_option {
    float: left;
    font-size: 15px;
    list-style: none;
    position: relative;
    height: 100%;
    width: 100%;
    text-align: relative;
    margin-right: 10px;
    letter-spacing: 0.5px;
    font-weight: 400
  }

  #zcart_chat .header_img {
    background-color: #fff;
    max-width: 56px;
    border-radius: 50%;
    line-height: 50px;
    float: left;
    margin: -20px 10px 10px 10px;
    border: 3px solid rgba(0, 0, 0, 0.21);
  }

  #zcart_chat .header_img img {
    border-radius: 50%;
    max-width: 50px;
    max-height: 50px;
    text-align: center;
    vertical-align: middle;
  }

  #zcart_chat .change_img img {
    width: 35px;
    margin: 0px 20px 0px 20px;
  }

  #zcart_chat .chat_option .agent {
    font-size: 12px;
    font-weight: 300;
  }

  #zcart_chat .chat_option .online {
    opacity: 0.7;
    font-size: 11px;
    font-weight: 300;
  }

  #zcart_chat .chat_color {
    display: block;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    margin: 10px;
    float: left;
  }

  #zcart_chat p,
  #zcart_chat a {
    -webkit-animation: zoomIn .5s cubic-bezier(.42, 0, .58, 1);
    animation: zoomIn .5s cubic-bezier(.42, 0, .58, 1);
  }

  #zcart_chat p {
    display: block;
    text-align: center;
    padding: 10px 20px;
    margin-top: 40px;
    color: #888
  }

  #zcart_chat .chat_field:not(.chat-composer-msg) {
    position: relative;
    margin: 5px 0 5px 0;
    width: 50%;
    font-family: 'Roboto';
    font-size: 12px;
    line-height: 30px;
    font-weight: 500;
    color: #4b4b4b;
    -webkit-font-smoothing: antialiased;
    font-smoothing: antialiased;
    border: none;
    outline: none;
    display: inline-block;
  }

  #zcart_chat .chat_field.chat_message {
    height: 30px;
    resize: none;
    overflow-y: auto;
    font-size: 13px;
    font-weight: 400;
    -webkit-appearance: none;
    appearance: none;
  }

  /* Prevent numeric spinner controls in chat input area */
  #zcart_chat input[type=number]::-webkit-outer-spin-button,
  #zcart_chat input[type=number]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  #zcart_chat input[type=number] {
    -moz-appearance: textfield;
    appearance: textfield;
  }

  #zcart_chat .chat_category {
    text-align: left;
  }

  #zcart_chat .chat_category {
    margin: 20px;
    background: rgba(0, 0, 0, 0.03);
    padding: 10px;
  }

  #zcart_chat .chat_category ul li {
    width: 80%;
    height: 30px;
    background: #fff;
    padding: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 10px;
    border-radius: 3px;
    border: 1px solid #e0e0e0;
    font-size: 13px;
    cursor: pointer;
    line-height: 30px;
    color: #888;
    text-align: center;
  }

  #zcart_chat .chat_category li:hover {
    background: #83c76d;
    color: #fff;
  }

  #zcart_chat .chat_category li.active {
    background: #83c76d;
    color: #fff;
  }

  #zcart_chat .chat-product-share {
    background: #fff;
    border-top: 1px solid #eee;
    padding: 8px;
    box-sizing: border-box;
  }

  #zcart_chat .chat-product-share-title {
    font-size: 11px;
    color: #666;
    margin-bottom: 6px;
  }

  #zcart_chat .chat-product-share-card {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: nowrap;
  }

  #zcart_chat .chat-product-share-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
    margin-left: auto;
  }

  #zcart_chat .chat-product-share-dismiss {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    min-width: 30px;
    min-height: 30px;
    padding: 0;
    margin: 0;
    border: 1px solid #bbb;
    border-radius: 50%;
    background: #f0f0f0;
    color: #333;
    font-size: 20px;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
    -webkit-appearance: none;
    appearance: none;
  }

  #zcart_chat .chat-product-share-dismiss:hover {
    background: #e0e0e0;
    border-color: #999;
    color: #000;
  }

  #zcart_chat .chat-product-share-media img {
    width: 34px;
    height: 34px;
    border-radius: 4px;
    object-fit: cover;
  }

  #zcart_chat .chat-product-share-body {
    flex: 1;
    min-width: 0;
  }

  #zcart_chat .chat-product-share-name {
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #333;
  }

  #zcart_chat .chat-product-share-price {
    font-size: 12px;
    color: #ff6a00;
    font-weight: 600;
  }

  #zcart_chat .chat-product-share-btn {
    border: 0;
    background: #ff7a00;
    color: #fff;
    border-radius: 16px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
  }

  #zcart_chat .chat-attachment-preview {
    display: none;
    clear: both;
    width: 100%;
    box-sizing: border-box;
    background: #f0f4f8;
    border-top: 1px solid #dde3ea;
    border-left: 3px solid #ff7a00;
    padding: 8px 10px;
  }

  #zcart_chat .chat-attachment-preview.chat-attachment-preview--visible {
    display: block;
  }

  #zcart_chat .chat-attachment-preview-inner {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  #zcart_chat .chat-attachment-preview-label {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #666;
  }

  #zcart_chat .chat-attachment-preview-row {
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 100%;
  }

  #zcart_chat .chat-attachment-preview-img {
    display: none;
    width: 44px;
    height: 44px;
    border-radius: 4px;
    object-fit: cover;
    flex-shrink: 0;
    border: 1px solid #ccc;
    background: #fff;
  }

  #zcart_chat .chat-attachment-preview-icon {
    display: none;
    flex-shrink: 0;
    width: 44px;
    text-align: center;
    color: #42a5f5;
    font-size: 26px;
    line-height: 44px;
  }

  #zcart_chat .chat-attachment-preview-name {
    flex: 1;
    min-width: 0;
    font-size: 12px;
    font-weight: 500;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-align: left;
  }

  #zcart_chat .chat-attachment-preview-remove {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    padding: 0;
    margin: 0;
    border: 1px solid #bbb;
    border-radius: 50%;
    background: #fff;
    color: #555;
    font-size: 18px;
    line-height: 26px;
    cursor: pointer;
    -webkit-appearance: none;
    appearance: none;
  }

  #zcart_chat .chat-attachment-preview-remove:hover {
    background: #eee;
    color: #111;
  }

  #zcart_chat .chat-shared-product-wrap {
    position: relative;
    display: inline-block;
    max-width: 100%;
  }

  #zcart_chat .chat-shared-product {
    display: flex;
    gap: 8px;
    align-items: center;
    min-width: 180px;
  }

  #zcart_chat .chat-shared-product-img {
    width: 38px;
    height: 38px;
    max-width: 100%;
    flex-shrink: 0;
    border-radius: 4px;
    object-fit: cover;
    background: #fff;
  }

  #zcart_chat .chat-shared-product-body {
    min-width: 0;
  }

  #zcart_chat .chat-shared-product-title {
    font-size: 12px;
    font-weight: 600;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  #zcart_chat .chat-shared-product-price {
    font-size: 11px;
    color: #ff8c1a;
    margin-top: 2px;
  }

  #zcart_chat .chat-shared-product-link {
    display: inline-block;
    margin-top: 3px;
    font-size: 11px;
    color: #fff;
    text-decoration: underline;
  }

  #zcart_chat .chat-sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }

  #zcart_chat .chat-composer-inner {
    display: flex;
    flex-direction: column;
    width: 100%;
    box-sizing: border-box;
  }

  #zcart_chat .chat-composer-row {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    width: 100%;
    box-sizing: border-box;
  }

  #zcart_chat .fchat_field.chat-composer {
    position: relative;
    display: block;
    width: 100%;
    box-sizing: border-box;
    padding: 6px 8px 8px;
    text-align: left;
    background: #fff;
    border-bottom-right-radius: 4px;
    border-bottom-left-radius: 4px;
    clear: both;
  }

  #zcart_chat .chat-composer-btn--attach {
    position: relative;
    overflow: hidden;
    cursor: pointer;
  }

  #zcart_chat .chat-composer-file-input {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    opacity: 0;
    cursor: pointer;
    font-size: 0;
    line-height: 0;
    z-index: 2;
  }

  #zcart_chat .chat-composer-btn {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    margin: 0;
    padding: 0;
    border: 0;
    border-radius: 4px;
    background: transparent;
    color: #888;
    cursor: pointer;
    box-shadow: none;
    line-height: 1;
    -webkit-appearance: none;
    appearance: none;
  }

  #zcart_chat label.chat-composer-btn {
    margin-bottom: 0;
  }

  #zcart_chat .chat-composer-btn:hover,
  #zcart_chat .chat-composer-btn:focus {
    color: #42a5f5;
    outline: none;
  }

  #zcart_chat .chat-composer-btn-icon {
    font-size: 18px;
    line-height: 1;
  }

  #zcart_chat .chat-composer-msg {
    flex: 1 1 auto;
    min-width: 0;
    width: auto !important;
    margin: 0 !important;
  }

  #zcart_chat .fchat_field:not(.chat-composer) {
    width: 100%;
    display: inline-block;
    text-align: center;
    background: #fff;
    border-bottom-right-radius: 4px;
    border-bottom-left-radius: 4px;
  }

  #zcart_chat .fchat_field2 {
    bottom: 0px;
    position: absolute;
    border-bottom-right-radius: 0px;
    border-bottom-left-radius: 0px;
    z-index: 999;
  }

  #zcart_chat .fchat_field a,
  #zcart_chat .fchat_field button {
    display: inline-block;
    text-align: center;
    border: 0;
    background: transparent;
    outline: none;
    box-shadow: none;
    -webkit-appearance: none;
    appearance: none;
  }

  #zcart_chat .fchat_field label.chat-composer-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    background: transparent;
    outline: none;
    box-shadow: none;
    -webkit-appearance: none;
    appearance: none;
  }

  #zcart_chat #fchat_camera {
    float: left;
    background: rgba(0, 0, 0, 0);
  }

  #zcart_chat .chat-attachment-block {
    margin-bottom: 4px;
    max-width: 100%;
    min-width: 0;
  }

  #zcart_chat .chat-attachment-block .chat-att-media {
    display: block;
    max-width: 100%;
    line-height: 0;
  }

  #zcart_chat .chat-att-thumb {
    max-width: 100%;
    width: auto;
    height: auto;
    max-height: 220px;
    border-radius: 4px;
    object-fit: contain;
    vertical-align: middle;
    display: block;
    margin-bottom: 4px;
  }

  #zcart_chat .chat-att-link {
    font-size: 11px;
    color: #42a5f5;
    word-break: break-all;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item .chat-attachment-block {
    max-width: 100%;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item .chat-att-thumb {
    max-width: 100%;
    height: auto;
  }

  #zcart_chat #chat_conversation {
    position: relative;
    background: #fff;
    margin: 0px;
    height: 300px;
    min-height: 0;
    font-size: 12px;
    line-height: 18px;
    overflow-y: auto;
    width: 100%;
    float: right;
    padding: 10px 0;
  }

  #zcart_chat .chat_converse_full_screen {
    height: 100%;
    max-height: 800px
  }

  #zcart_chat .chat .chat_converse .chat_msg_item {
    position: relative;
    margin: 2px 0;
    padding: 8px 10px;
    max-width: 65%;
    display: block;
    word-wrap: break-word;
    overflow-wrap: break-word;
    -webkit-animation: zoomIn .5s cubic-bezier(.42, 0, .58, 1);
    animation: zoomIn .5s cubic-bezier(.42, 0, .58, 1);
    clear: both;
    z-index: 999;
  }

  #zcart_chat .status {
    margin: 45px -50px 0 0;
    float: right;
    display: block;
    font-size: 11px;
    opacity: 0.3;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item .chat_avatar {
    height: 34px;
    line-height: 34px;
    border: 1px solid #d3d3d3;
    position: absolute;
    top: 0;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item.chat_msg_item_admin .chat_avatar {
    left: -42px;
    background: rgba(0, 0, 0, 0.03);
  }

  #zcart_chat .chat .chat_converse .chat_msg_item.chat_msg_item_user .chat_avatar {
    right: -42px;
    background: rgba(0, 0, 0, 0.6);
  }

  #zcart_chat .chat .chat_converse .chat_msg_item .chat_avatar,
  #zcart_chat .chat_avatar img {
    max-width: 34px;
    max-height: 34px;
    text-align: center;
    vertical-align: middle;
    border-radius: 50%;
    opacity: 0.9;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item.chat_msg_item_admin {
    margin-left: 47px;
    float: left;
    border-radius: 10px 10px 10px 0px;
    background: rgba(0, 0, 0, 0.07);
    color: #666;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item.chat_msg_item_user {
    margin-right: 10px;
    float: right;
    border-radius: 10px 10px 0px 10px;
    background: #42a5f5;
    color: #eceff1;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item.chat_msg_item_admin:before {
    content: '';
    position: absolute;
    top: 15px;
    left: -12px;
    z-index: 998;
    border: 6px solid transparent;
    border-right-color: rgba(255, 255, 255, .4);
  }

  #zcart_chat .chat_form .get-notified label {
    color: #077ad6;
    font-weight: 600;
    font-size: 11px;
  }

  #zcart_chat input {
    position: relative;
    width: 90%;
    font-family: 'Roboto';
    font-size: 12px;
    line-height: 20px;
    font-weight: 500;
    color: #4b4b4b;
    -webkit-font-smoothing: antialiased;
    font-smoothing: antialiased;
    outline: none;
    background: #fff;
    display: inline-block;
    resize: none;
    padding: 5px;
    border-radius: 3px;
  }

  #zcart_chat .chat_form .get-notified input {
    margin: 2px 0 0 0;
    border: 1px solid #83c76d;
  }

  #zcart_chat .chat_form .get-notified i {
    background: #83c76d;
    width: 30px;
    height: 32px;
    font-size: 18px;
    color: #fff;
    line-height: 30px;
    font-weight: 600;
    text-align: center;
    margin: 2px 0 0 -30px;
    position: absolute;
    border-radius: 3px;
  }

  #zcart_chat .chat_form .message_form {
    margin: 10px 0 0 0;
  }

  #zcart_chat .chat_form .message_form input {
    margin: 5px 0 5px 0;
    border: 1px solid #e0e0e0;
  }

  #zcart_chat .chat_form .message_form textarea {
    margin: 5px 0 5px 0;
    border: 1px solid #e0e0e0;
    position: relative;
    width: 90%;
    font-family: 'Roboto';
    font-size: 12px;
    line-height: 20px;
    font-weight: 500;
    color: #4b4b4b;
    -webkit-font-smoothing: antialiased;
    font-smoothing: antialiased;
    outline: none;
    background: #fff;
    display: inline-block;
    resize: none;
    padding: 5px;
    border-radius: 3px;
  }

  #zcart_chat .chat_form .message_form button {
    margin: 5px 0 5px 0;
    border: 1px solid #e0e0e0;
    position: relative;
    width: 95%;
    font-family: 'Roboto';
    font-size: 12px;
    line-height: 20px;
    font-weight: 500;
    color: #fff;
    -webkit-font-smoothing: antialiased;
    font-smoothing: antialiased;
    outline: none;
    background: #fff;
    display: inline-block;
    resize: none;
    padding: 5px;
    border-radius: 3px;
    background: #83c76d;
    cursor: pointer;
  }

  #zcart_chat strong.chat_time {
    padding: 0 1px 1px 0;
    font-weight: 500;
    font-size: 8px;
    display: block;
  }

  /*Chatbox scrollbar*/

  /*::-webkit-scrollbar {
 width: 6px;
 }

 ::-webkit-scrollbar-track {
 border-radius: 0;
 }

 ::-webkit-scrollbar-thumb {
 margin: 2px;
 border-radius: 10px;
 background: rgba(0, 0, 0, 0.2);
 }*/
  /*Element state*/

  #zcart_chat .is-active {
    -webkit-transform: rotate(180deg);
    transform: rotate(180deg);
    -webkit-transition: all 1s ease-in-out;
    transition: all 1s ease-in-out;
  }

  #zcart_chat .is-float {
    box-shadow: 0 0 6px rgba(0, 0, 0, .16), 0 6px 12px rgba(0, 0, 0, .32);
  }

  #zcart_chat .is-loading {
    display: block;
    -webkit-animation: load 1s cubic-bezier(0, .99, 1, 0.6) infinite;
    animation: load 1s cubic-bezier(0, .99, 1, 0.6) infinite;
  }

  /*Animation*/

  @-webkit-keyframes zoomIn {
    0% {
      -webkit-transform: scale(0);
      transform: scale(0);
      opacity: 0.0;
    }

    100% {
      -webkit-transform: scale(1);
      transform: scale(1);
      opacity: 1;
    }
  }

  @keyframes zoomIn {
    0% {
      -webkit-transform: scale(0);
      transform: scale(0);
      opacity: 0.0;
    }

    100% {
      -webkit-transform: scale(1);
      transform: scale(1);
      opacity: 1;
    }
  }

  @-webkit-keyframes load {
    0% {
      -webkit-transform: scale(0);
      transform: scale(0);
      opacity: 0.0;
    }

    50% {
      -webkit-transform: scale(1.5);
      transform: scale(1.5);
      opacity: 1;
    }

    100% {
      -webkit-transform: scale(1);
      transform: scale(1);
      opacity: 0;
    }
  }

  @keyframes load {
    0% {
      -webkit-transform: scale(0);
      transform: scale(0);
      opacity: 0.0;
    }

    50% {
      -webkit-transform: scale(1.5);
      transform: scale(1.5);
      opacity: 1;
    }

    100% {
      -webkit-transform: scale(1);
      transform: scale(1);
      opacity: 0;
    }
  }

  /* SMARTPHONES PORTRAIT */

  @media only screen and (min-width: 300px) {
    #zcart_chat .chat {
      width: 250px;
    }
  }

  /* SMARTPHONES LANDSCAPE */
  @media only screen and (min-width: 480px) {
    #zcart_chat .chat {
      width: 300px;
    }

    #zcart_chat .chat_field:not(.chat-composer-msg) {
      width: 65%;
    }
  }

  /* TABLETS PORTRAIT */
  @media only screen and (min-width: 768px) {
    #zcart_chat .chat {
      width: 300px;
    }

    #zcart_chat .chat_field:not(.chat-composer-msg) {
      width: 65%;
    }
  }

  /* TABLET LANDSCAPE / DESKTOP */
  @media only screen and (min-width: 1024px) {
    #zcart_chat .chat {
      width: 300px;
    }

    #zcart_chat .chat_field:not(.chat-composer-msg) {
      width: 65%;
    }
  }

  /*Color Options*/

  #zcart_chat .blue .fchat {
    background: #42a5f5;
    color: #fff;
  }

  #zcart_chat .blue .chat {
    background: #42a5f5;
    color: #999;
  }


  /* Ripple */

  #zcart_chat .ink {
    display: block;
    position: absolute;
    background: rgba(38, 50, 56, 0.4);
    border-radius: 100%;
    -moz-transform: scale(0);
    -ms-transform: scale(0);
    webkit-transform: scale(0);
    -webkit-transform: scale(0);
    transform: scale(0);
  }

  /*animation effecid	#zcart_chat .ink.animate {
 -webkit-animation: ripple 0.5s ease-in-out;
  animation: ripple 0.5s ease-in-out;
 }

 @-webkit-keyframes ripple {
 /*scale the element to 250% to safely cover the entire link and fade it out*/

  100% {
    opacity: 0;
    -moz-transform: scale(5);
    -ms-transform: scale(5);
    webkit-transform: scale(5);
    -webkit-transform: scale(5);
    transform: scale(5);
  }
  }

  @keyframes ripple {
    /*scale the element to 250% to safely cover the entire link and fade it out*/

    100% {
      opacity: 0;
      -moz-transform: scale(5);
      -ms-transform: scale(5);
      webkit-transform: scale(5);
      -webkit-transform: scale(5);
      transform: scale(5);
    }
  }

  ::-webkit-input-placeholder {
    /* Chrome */
    color: #bbb;
  }

  :-ms-input-placeholder {
    /* IE 10+ */
    color: #bbb;
  }

  ::-moz-placeholder {
    /* Firefox 19+ */
    color: #bbb;
  }

  :-moz-placeholder {
    /* Firefox 4 - 18 */
    color: #bbb;
  }
</style>
