<script type="text/javascript">
  function attachMerchantChatAutoScrollObserver() {
    var el = document.getElementById('chatConversation');
    if (!el || el._merchantChatAutoScrollObserver) {
      return;
    }
    el._merchantChatAutoScrollObserver = true;
    var timer = null;
    var obs = new MutationObserver(function() {
      if (timer) {
        clearTimeout(timer);
      }
      timer = setTimeout(function() {
        if (typeof updateScroll === 'function') {
          updateScroll('conversationBox');
        }
      }, 40);
    });
    obs.observe(el, { childList: true, subtree: true });
  }

  var MerchantChatAttachmentPreview = (function() {
    var objectUrl = null;

    function $strip() {
      return $('#merchant-chat-attachment-preview');
    }

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
        var fi = document.getElementById('merchantChatFile');
        if (fi) {
          fi.value = '';
        }
        hideStrip($strip());
      },
      updateFromInput: function() {
        var fi = document.getElementById('merchantChatFile');
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

  ;
  (function($, window, document) {
    var sharePrefix = '[product_share]';

    function parseSharedProduct(text) {
      if (!text || typeof text !== 'string' || text.indexOf(sharePrefix) !== 0) return null;
      try {
        return JSON.parse(text.substring(sharePrefix.length));
      } catch (e) {
        return null;
      }
    }

    function sharedProductNode(payload) {
      return $('<div>').addClass('shared-product-card').append(
        $('<img/>').addClass('shared-product-thumb').attr('src', payload.image || '').attr('alt', payload.title || 'product')
      ).append(
        $('<div>').addClass('shared-product-meta').append(
          $('<div>').addClass('shared-product-title').text(payload.title || '')
        ).append(
          $('<div>').addClass('shared-product-price').text(payload.price || '')
        ).append(
          $('<a>').addClass('shared-product-link').attr('href', payload.url || '#').attr('target', '_blank').text('View')
        )
      );
    }

    function prepareNewConversation(msgObj) {
      var link = '{{ livechat_support_route('chat_conversation.show', ['chat' => '__CID__']) }}';
      link = link.replace('__CID__', msgObj.conversation_id);

      return $('<div>').attr('id', 'chat-' + msgObj.customer_id).addClass('mp-chat-list__item sidebarBody is-unread').append(
        $('<a>').attr('href', 'javascript:void(0)').attr('data-link', link).addClass('get-content mp-chat-list__link').append(
          $('<img/>').attr('src', msgObj.avatar).attr('alt', '{{ trans('app.avatar') }}').addClass('mp-chat-list__avatar img-circle')
        ).append(
          $('<div>').addClass('mp-chat-list__meta sideBar-main').append(
            $('<div>').addClass('mp-chat-list__row').append(
              $('<span>').addClass('name-meta strong').text(msgObj.sender)
            ).append(
              $('<span>').addClass('time-meta').text(msgObj.time)
            )
          ).append(
            $('<div>').addClass('mp-chat-list__row mp-chat-list__row--sub').append(
              $('<p>').addClass('excerpt strong').text(getExcerptMsg(msgObj.text, msgObj.attachments))
            ).append(
              $('<span>').addClass('mp-chat-list__badge label label-primary flat').text(msgObj.status)
            )
          )
        )
      );
    }

    function isAttachmentPlaceholderText(txt) {
      if (txt == null || txt === '') {
        return true;
      }
      var plain = String(txt).replace(/<[^>]*>/g, '').trim().toLowerCase();
      return plain === '' || plain === '[attachment]';
    }

    function buildMerchantChatAttachmentUrl(att) {
      var path = att.path;
      if (path) {
        return '/image/' + String(path).split('/').map(function(seg) {
          return encodeURIComponent(seg);
        }).join('/');
      }
      if (att.url) {
        try {
          var a = document.createElement('a');
          a.href = att.url;
          if (a.pathname && a.pathname.indexOf('/image/') === 0) {
            return a.pathname + (a.search || '');
          }
        } catch (e) {}
        return att.url;
      }
      return '';
    }

    function attachmentExtensionForPreview(att) {
      var ext = String(att.extension || '').toLowerCase().replace(/^\./, '');
      if (ext) {
        return ext;
      }
      var name = String(att.name || '');
      var m = name.match(/\.([a-z0-9]+)$/i);
      return m ? m[1].toLowerCase() : '';
    }

    function appendAttachmentNodes(contentNode, attachments) {
      if (!attachments || !attachments.length) {
        return;
      }
      attachments.forEach(function(att) {
        var ext = attachmentExtensionForPreview(att);
        var isImg = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].indexOf(ext) !== -1;
        var url = buildMerchantChatAttachmentUrl(att);
        if (isImg && url) {
          contentNode.append(
            $('<a>').attr('href', url).attr('target', '_blank').attr('rel', 'noopener').append(
              $('<img>').attr('src', url).attr('alt', att.name || '').css({
                maxWidth: '220px',
                height: 'auto',
                borderRadius: '4px',
                display: 'block'
              })
            )
          );
        } else if (url) {
          var $lnk = $('<a>').attr('href', url).attr('target', '_blank').attr('rel', 'noopener')
            .addClass('btn btn-default btn-xs').css('margin-top', '4px');
          $lnk.append($('<i>').addClass('fa fa-paperclip'));
          $lnk.append(document.createTextNode(' ' + (att.name || 'File')));
          contentNode.append($lnk);
        }
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
        if (sameDay(d, today)) return '{{ trans('theme.today') !== 'theme.today' ? trans('theme.today') : 'Today' }}';
        if (sameDay(d, yday)) return '{{ trans('theme.yesterday') !== 'theme.yesterday' ? trans('theme.yesterday') : 'Yesterday' }}';
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

    function ensureConversationDaySep(boxSelector, isoOrDate) {
      var $box = $(boxSelector);
      if (!$box.length) return;
      var key = chatDayKey(isoOrDate);
      if (!key) return;
      var last = $box.children('.mp-chat-day-sep').last();
      if (last.length && String(last.attr('data-day')) === key) return;
      $box.append(
        $('<div>').addClass('mp-chat-day-sep').attr('data-day', key).append(
          $('<span>').text(formatChatDayLabel(isoOrDate))
        )
      );
    }

    function prepareNewChatMsg(txt, who, attachments, timeLabel, createdAt) {
      who = who || 'sender';
      attachments = attachments || [];
      var shared = parseSharedProduct(txt);
      var contentNode = $('<div>').addClass('message-text');
      if (shared) {
        contentNode.append(sharedProductNode(shared));
      } else if (attachments.length) {
        appendAttachmentNodes(contentNode, attachments);
        var cap = (txt || '').trim();
        if (cap && !isAttachmentPlaceholderText(cap)) {
          contentNode.append($('<div>').css('margin-top', '6px').text(cap));
        }
      } else {
        contentNode.text(txt);
      }

      var clock = timeLabel || formatChatClock(createdAt || new Date().toISOString());
      var isOut = who === 'sender';
      var bubbleClass = isOut ? 'mp-chat-bubble mp-chat-bubble--out' : 'mp-chat-bubble mp-chat-bubble--in';

      return $('<div>').addClass(bubbleClass).attr('data-created-at', createdAt || new Date().toISOString()).append(
        $('<div>').addClass(who).append(contentNode).append(
          $('<time>').addClass('message-time').attr('datetime', createdAt || '').text(clock)
        )
      );
    }

    function getExcerptMsg(text, attachments) {
      var shared = parseSharedProduct(text);
      if (shared && shared.title) {
        return '[Product] ' + shared.title;
      }
      if (attachments && attachments.length) {
        if (isAttachmentPlaceholderText(text)) {
          return '[Attachment]';
        }
      }
      return (text || '').substring(0, 120);
    }

    function markAsUnread(chatNode) {
      var label = chatNode.find(".label");

      if (label.hasClass('hide')) {
        label.removeClass('hide'); // Show unread label
      } else {
        chatNode.find(".name-meta, p.excerpt").addClass('strong');
      }
    }

    function markAsRead(chatNode) {
      chatNode.find(".name-meta, p.excerpt").removeClass('strong');
      chatNode.find(".label").addClass('hide'); // Hide unread label
    }

    $(document).ready(function() {
      attachMerchantChatAutoScrollObserver();

      $('body').on('change', '#merchantChatFile', function() {
        MerchantChatAttachmentPreview.updateFromInput();
      });

      $('body').on('click', '#merchant_remove_attachment', function(e) {
        e.preventDefault();
        MerchantChatAttachmentPreview.clear();
      });

      $('body').on('click', 'a.get-content', function(e) {
        e.preventDefault();
        $('.loader').show();
        var node = $(this);
        $.get(node.data('link'), function(data) {
          $('.loader').hide();
          $('#chatConversation').html(data); //Display the result
          updateScroll('conversationBox'); //Scroll to bottom
          markAsRead(node); // Mark as read
        });
      });

      function merchantChatCsrf() {
        var meta = $('meta[name="csrf-token"]').attr('content');
        if (meta) return meta;
        var formToken = $('#chat-form input[name="_token"]').val();
        return formToken || '';
      }

      function setMerchantChatAjaxHeaders(xhr) {
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        var csrf = merchantChatCsrf();
        if (csrf) {
          xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
        }
      }

      function sendMerchantChatReply() {
        var $btn = $('#send-btn');
        var form = $('#chat-form');
        if (!form.length || !$btn.length) {
          return;
        }
        if ($btn.data('sending')) {
          return;
        }

        var $textarea = form.find('textarea[name="message"]');
        var msg = $.trim($textarea.val() || '');
        var fileInput = form.find('input[name="photo"]')[0];
        var hasFile = !!(fileInput && fileInput.files && fileInput.files.length);
        if (msg === '' && !hasFile) {
          return;
        }

        var postUrl = form.attr('action');
        if (!postUrl) {
          if (window.console && console.error) {
            console.error('[merchant-chat] missing form action');
          }
          return;
        }

        $btn.data('sending', true);

        var fdFile = hasFile ? fileInput.files[0] : null;
        var fd = new FormData();
        fd.append('message', msg);
        var csrf = merchantChatCsrf();
        if (csrf) {
          fd.append('_token', csrf);
        }
        if (fdFile) {
          fd.append('photo', fdFile);
        }

        if (fdFile) {
          MerchantChatAttachmentPreview.clear();
        }

        // Optimistic UI
        var pendingAtt = [];
        if (fdFile) {
          var ext = (fdFile.name.split('.').pop() || '').toLowerCase();
          pendingAtt = [{
            url: URL.createObjectURL(fdFile),
            name: fdFile.name,
            extension: ext || 'file'
          }];
        }
        var pendingNode = prepareNewChatMsg(msg || (hasFile ? '[attachment]' : ''), 'sender', pendingAtt)
          .attr('data-pending', '1');
        ensureConversationDaySep('#conversationBox', new Date().toISOString());
        $("#conversationBox").append(pendingNode);
        updateScroll('conversationBox');
        $textarea.val(''); // clear typed text after send starts
        var ajaxStartedAt = Date.now();
        if (window.console && console.log) {
          console.log('[chat-ws-web ' + new Date().toISOString() + '] AJAX reply start url=' + postUrl);
        }

        var openChatboxEarly = document.querySelector('[id^="openChatbox-"]');
        if (openChatboxEarly && openChatboxEarly.id) {
          var earlyCustomerId = openChatboxEarly.id.replace('openChatbox-', '');
          var earlyChatNode = $('#chat-' + earlyCustomerId);
          if (earlyChatNode.length) {
            earlyChatNode.find("p.excerpt").text(getExcerptMsg(msg || (hasFile ? '[attachment]' : ''), pendingAtt));
            earlyChatNode.find(".time span").text(formatChatClock(new Date().toISOString()));
          }
        }

        var response = '';

        $.ajax({
          url: postUrl,
          type: 'POST',
          data: fd,
          processData: false,
          contentType: false,
          beforeSend: setMerchantChatAjaxHeaders,
          complete: function(xhr) {
            $btn.data('sending', false);
            var ajaxMs = Date.now() - ajaxStartedAt;
            if (window.console && console.log) {
              console.log('[chat-ws-web ' + new Date().toISOString() + '] AJAX reply done status=' + xhr.status + ' ms=' + ajaxMs);
            }
            switch (xhr.status) {
              case 200: {
                if (fileInput) {
                  fileInput.value = '';
                }
                var replyMsg = msg;
                var attachments = [];
                var replyId = null;
                var replyTime = null;
                var replyCreatedAt = null;
                try {
                  var parsed = JSON.parse(xhr.responseText);
                  if (parsed && typeof parsed === 'object') {
                    if (parsed.message != null) {
                      replyMsg = parsed.message;
                    }
                    attachments = parsed.attachments || [];
                    if (parsed.reply_id) {
                      replyId = parsed.reply_id;
                    }
                    replyTime = parsed.time || null;
                    replyCreatedAt = parsed.created_at || null;
                  }
                } catch (err) {
                  // Legacy non-JSON success body
                }
                if (pendingNode && pendingNode.length) {
                  if (replyId) {
                    pendingNode.attr('data-reply-id', replyId).removeAttr('data-pending');
                  } else {
                    pendingNode.removeAttr('data-pending');
                  }
                  if (replyCreatedAt) {
                    pendingNode.attr('data-created-at', replyCreatedAt);
                  }
                  if (replyTime || replyCreatedAt) {
                    pendingNode.find('.message-time').text(replyTime || formatChatClock(replyCreatedAt));
                  }
                  if (attachments && attachments.length) {
                    var upgraded = prepareNewChatMsg(replyMsg || (hasFile ? '[attachment]' : ''), 'sender', attachments, replyTime, replyCreatedAt);
                    if (replyId) {
                      upgraded.attr('data-reply-id', replyId);
                    }
                    pendingNode.replaceWith(upgraded);
                    pendingNode = upgraded;
                  }
                } else if (!(replyId && $('#conversationBox [data-reply-id="' + replyId + '"]').length)) {
                  ensureConversationDaySep('#conversationBox', replyCreatedAt || new Date().toISOString());
                  response = prepareNewChatMsg(replyMsg || (hasFile ? '[attachment]' : ''), 'sender', attachments, replyTime, replyCreatedAt);
                  if (replyId) {
                    response.attr('data-reply-id', replyId);
                  }
                }

                var openChatbox = document.querySelector('[id^="openChatbox-"]');
                if (openChatbox && openChatbox.id) {
                  var customerId = openChatbox.id.replace('openChatbox-', '');
                  var chatNode = $('#chat-' + customerId);
                  if (chatNode.length) {
                    chatNode.find("p.excerpt").text(getExcerptMsg(replyMsg, attachments));
                    chatNode.find(".time span").text(replyTime || formatChatClock(replyCreatedAt || new Date().toISOString()));
                  }
                }
                break;
              }
              case 401:
              case 403:
              case 419:
                $('#conversationBox [data-pending="1"]').remove();
                $textarea.val(msg); // restore typed text
                response = $('<p>').addClass('text-danger').css({margin: '8px 12px'}).text("{!! trans('messages.session_expired') !!}");
                break;
              default:
                $('#conversationBox [data-pending="1"]').remove();
                $textarea.val(msg); // restore typed text
                response = $('<p>').addClass('text-danger').css({margin: '8px 12px'}).text("{!! trans('messages.failed') !!}");
            }

            if (response) {
              $("#conversationBox").append(response);
            }

            updateScroll('conversationBox');
          },
        });
      }

      $('body').on('click', '#send-btn', function(e) {
        e.preventDefault();
        sendMerchantChatReply();
      });

      $('body').on('keydown', '#chat-form textarea[name="message"]', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          sendMerchantChatReply();
        }
      });

      var wsScheme = '{{ config('chat_socket.scheme') }}';
      var wsHost = '{{ config('chat_socket.client_host') }}';
      var wsPort = '{{ (int) config('chat_socket.port') }}';
      var wsPath = '{{ trim((string) config('chat_socket.client_path', '')) }}';
      // Build a browser-safe WS URL (never use 0.0.0.0; don't double-append port).
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
      var room = '{{ get_vendor_chat_room_id() }}';
      var socket = null;
      var CHAT_WS_DEBUG = {{ config('chat_socket.debug') ? 'true' : 'false' }};

      function chatWsLog() {
        if (!CHAT_WS_DEBUG || !window.console || !console.log) return;
        var args = Array.prototype.slice.call(arguments);
        args.unshift('[chat-ws]');
        console.log.apply(console, args);
      }

      function connectSocket() {
        chatWsLog('connecting', wsUrl, 'room=', room);
        try {
          socket = new WebSocket(wsUrl);
        } catch (e) {
          chatWsLog('connect ERROR', e && e.message ? e.message : e);
          return;
        }

        socket.onopen = function() {
          chatWsLog('OPEN → subscribe', room);
          socket.send(JSON.stringify({
            action: 'subscribe',
            room: room
          }));
        };

        socket.onmessage = function(event) {
          var tRecv = Date.now();
          var parsed;
          try {
            parsed = JSON.parse(event.data);
          } catch (e) {
            chatWsLog('bad JSON', event.data);
            return;
          }

          if (parsed && parsed.ok && parsed.subscribed) {
            chatWsLog('SUBSCRIBED', parsed.subscribed, parsed._debug || '', 'server_ts=', parsed.server_ts);
            return;
          }

          if (parsed && parsed.ok && parsed.pong) {
            return;
          }

          if (!parsed || parsed.event !== 'chat.message' || !parsed.data) {
            chatWsLog('ignored frame', parsed);
            return;
          }

          var result = parsed.data;
          var senderType = result.sender_type || '';
          var pubMs = result._published_ms || null;
          var clientLag = pubMs ? (tRecv - pubMs) : null;
          var serverLag = parsed._debug && parsed._debug.lag_ms != null ? parsed._debug.lag_ms : null;
          chatWsLog(
            'MESSAGE',
            'sender=' + senderType,
            'reply_id=' + (result.reply_id || '-'),
            'client_lag_ms=' + clientLag,
            'server_lag_ms=' + serverLag,
            'debug=', parsed._debug || null,
            'text=', String(result.text || '').substring(0, 40)
          );

          // Merchant messages from vendor app / other tabs → sync into open web chat
          if (senderType === 'merchant') {
            if (result.reply_id && $('#conversationBox [data-reply-id="' + result.reply_id + '"]').length) {
              chatWsLog('dedupe merchant reply_id', result.reply_id);
              return;
            }

            // Same-tab send: upgrade optimistic pending bubble instead of duplicating.
            var pendingMine = $('#conversationBox [data-pending="1"]').last();
            if (pendingMine.length) {
              if (result.reply_id) {
                pendingMine.attr('data-reply-id', result.reply_id);
              }
              pendingMine.removeAttr('data-pending');
              if (result.created_at) {
                pendingMine.attr('data-created-at', result.created_at);
              }
              if (result.time || result.created_at) {
                pendingMine.find('.message-time').text(result.time || formatChatClock(result.created_at));
              }
              chatWsLog('upgraded pending bubble reply_id=', result.reply_id);
              return;
            }

            var mCustomerId = result.customer_id || null;
            var mChatNode = mCustomerId ? $('#chat-' + mCustomerId) : $();
            if (mChatNode.length === 0 && result.conversation_id) {
              mChatNode = $("#leftsidebar .sidebarBody a.get-content").filter(function() {
                var link = String($(this).data('link') || '');
                return link.indexOf('/' + result.conversation_id) !== -1;
              }).closest('.sidebarBody');
            }

            if (mChatNode.length) {
              var mOpenCustomerId = mCustomerId;
              if (!mOpenCustomerId && mChatNode.attr('id')) {
                mOpenCustomerId = String(mChatNode.attr('id')).replace('chat-', '');
              }
              var mOpenChatbox = mOpenCustomerId ? document.getElementById("openChatbox-" + mOpenCustomerId) : null;
              if (mOpenChatbox) {
                ensureConversationDaySep('#conversationBox', result.created_at || new Date().toISOString());
                var mResponse = prepareNewChatMsg(result.text, 'sender', result.attachments || [], result.time, result.created_at);
                if (result.reply_id) {
                  mResponse.attr('data-reply-id', result.reply_id);
                }
                $("#conversationBox").append(mResponse);
                updateScroll('conversationBox');
                chatWsLog('appended merchant to open chat');
              } else {
                chatWsLog('merchant msg but chatbox not open for customer', mOpenCustomerId);
              }
              mChatNode.find("p.excerpt").text(getExcerptMsg(result.text, result.attachments));
              mChatNode.find(".time span").text(result.time || formatChatClock(result.created_at || new Date().toISOString()));
            } else {
              chatWsLog('merchant msg: no sidebar node for customer_id=', mCustomerId, 'conv=', result.conversation_id);
            }
            return;
          }

          if (senderType !== 'customer') {
            chatWsLog('ignored sender_type', senderType);
            return;
          }

        // Check if the coversation is already exist
        var customerId = result.customer_id || null;
        var chatNode = customerId ? $('#chat-' + customerId) : $();
        if (chatNode.length === 0 && result.conversation_id) {
          chatNode = $("#leftsidebar .sidebarBody a.get-content").filter(function() {
            var link = String($(this).data('link') || '');
            return link.indexOf('/' + result.conversation_id) !== -1;
          }).closest('.sidebarBody');
        }
        if (chatNode.length === 0) { //It message is from a new customer
          var newChat = prepareNewConversation(result);

          $("#leftsidebar .sidebarContent").append(newChat);
          chatWsLog('new conversation sidebar row');
        } else { //Old customer
          var openCustomerId = customerId;
          if (!openCustomerId && chatNode.attr('id')) {
            openCustomerId = String(chatNode.attr('id')).replace('chat-', '');
          }
          var openChatbox = openCustomerId ? document.getElementById("openChatbox-" + openCustomerId) : null;
          if (openChatbox) { //The chatbox is already open
            if (result.reply_id && $('#conversationBox [data-reply-id="' + result.reply_id + '"]').length) {
              chatWsLog('dedupe customer reply_id', result.reply_id);
              return;
            }
            ensureConversationDaySep('#conversationBox', result.created_at || new Date().toISOString());
            response = prepareNewChatMsg(result.text, 'receiver', result.attachments || [], result.time, result.created_at);
            if (result.reply_id) {
              response.attr('data-reply-id', result.reply_id);
            }
            $("#conversationBox").append(response);
            updateScroll('conversationBox'); //Scroll to bottom
            chatWsLog('appended customer to open chat');
          } else { //Chatbox is not open
            markAsUnread(chatNode); // Mark as unread
            chatWsLog('customer msg → mark unread');
          }

          chatNode.find("p.excerpt").text(getExcerptMsg(result.text, result.attachments)); // Update the excerpt on left menu
          chatNode.find(".time span").text(result.time || formatChatClock(result.created_at || new Date().toISOString())); // Update the time on left menu
        }

        };

        socket.onclose = function(ev) {
          chatWsLog('CLOSE code=', ev.code, 'reason=', ev.reason || '', '→ reconnect 1200ms');
          setTimeout(connectSocket, 1200);
        };

        socket.onerror = function() {
          chatWsLog('ERROR on socket');
        };
      }

      connectSocket();
    });
  }(window.jQuery, window, document));
</script>

<style type="text/css">
  #chatbox {
    overflow: hidden;
    top: 19px;
    height: calc(100% - 38px);
    margin: auto;
    padding: 0;
    color: #666;
  }

  #chatbox .chatContent {
    width: 100%;
    overflow: hidden;
    margin: 0;
    padding: 0;
  }

  .side {
    padding: 0;
    margin: 0;
  }

  #leftsidebar {
    padding: 0;
    margin: 0;
    /*height: 100%;*/
    width: 100%;
    z-index: 1;
    position: relative;
    display: block;
    top: 0;
  }

  #leftsidebar a {
    color: #666;
  }

  .heading {
    padding: 10px 16px 10px 15px;
    margin: 0;
    height: 60px;
    width: 100%;
    background-color: #eee;
    z-index: 1000;
  }

  .heading-avatar {
    padding: 0;
    cursor: pointer;

  }

  .heading-avatar img {
    border-radius: 50%;
    height: 40px;
    width: 40px;
  }

  .heading-name {
    padding: 0 !important;
    /*cursor: pointer;*/
  }

  .heading-name-meta {
    font-weight: 700;
    font-size: 100%;
    padding: 5px;
    padding-bottom: 0;
    text-align: left;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #000;
    display: block;
  }

  .heading-online {
    display: none;
    padding: 0 5px;
    font-size: 12px;
    color: #93918f;
  }

  .heading-compose {
    padding: 0;
  }

  .heading-compose i {
    text-align: center;
    padding: 5px;
    color: #93918f;
    cursor: pointer;
  }

  .heading-dot {
    padding: 0;
    margin-left: 10px;
  }

  .heading-dot i {
    text-align: right;
    padding: 5px;
    color: #93918f;
    cursor: pointer;
  }

  .searchBox {
    padding: 0 !important;
    margin: 0 !important;
    height: 60px;
    width: 100%;
  }

  .searchBox-inner {
    height: 100%;
    width: 100%;
    padding: 10px !important;
    background-color: #fbfbfb;
  }


  /*#searchBox-inner input {
      box-shadow: none;
    }*/

  .searchBox-inner input:focus {
    outline: none;
    border: none;
    box-shadow: none;
  }

  .sidebarContent {
    padding: 0 !important;
    margin: 0 !important;
    background-color: #fff;
    min-height: 350px;
    max-height: 500px;
    overflow-y: auto;
    border: 1px solid #f7f7f7;
    height: calc(100% - 120px);
  }

  .sidebarBody {
    position: relative;
    padding: 10px !important;
    border-bottom: 1px solid #f7f7f7;
    height: 72px;
    margin: 0 !important;
    cursor: pointer;
  }

  .sidebarBody.active,
  .sidebarBody:hover {
    background-color: #f2f2f2;
  }

  .sidebarContent img {
    height: 49px;
    width: 49px;
  }

  .sideBar-main .row {
    padding: 0 !important;
    margin: 0 !important;
  }

  .sideBar-name {
    padding: 0 !important;
  }

  .name-meta {
    font-size: 1.2em;
    text-align: left;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #000;
  }

  .name-meta .label {
    zoom: 80%;
  }

  .sidebarContent .time {
    padding: 10px 0px !important;
  }

  .time-meta {
    text-align: right;
    font-size: 11px;
    /*padding: 1% !important;*/
    color: rgba(0, 0, 0, .4);
    vertical-align: baseline;
  }

  .composeBox {
    padding: 0 !important;
    margin: 0 !important;
    height: 60px;
    width: 100%;
  }

  .composeBox-inner {
    height: 100%;
    width: 100%;
    padding: 10px !important;
    background-color: #fbfbfb;
  }

  .composeBox-inner input:focus {
    outline: none;
    border: none;
    box-shadow: none;
  }

  .compose-sideBar {
    padding: 0 !important;
    margin: 0 !important;
    background-color: #fff;
    overflow-y: auto;
    border: 1px solid #f7f7f7;
    height: calc(100% - 160px);
  }

  /*Conversation*/
  .conversation {
    padding: 0 !important;
    margin: 0 !important;
    height: 100%;
    /*width: 100%;*/
    border-left: 1px solid rgba(0, 0, 0, .08);
    overflow-y: auto;
  }

  .message {
    padding: 20px 0 !important;
    margin: 0 !important;
    background: url("w.jpg") no-repeat fixed center;
    background-size: cover;
    overflow-y: auto;
    border: 1px solid #f7f7f7;
    height: 350px;
    /*height: calc(100% - 120px);*/
  }

  .message-previous {
    margin: 0 !important;
    padding: 0 !important;
    height: auto;
    width: 100%;
  }

  .previous {
    font-size: 15px;
    text-align: center;
    padding: 10px !important;
    cursor: pointer;
  }

  .previous a {
    text-decoration: none;
    font-weight: 700;
  }

  .message-body {
    margin: 0 0 3px 0 !important;
    padding: 0 !important;
    width: auto;
    height: auto;
  }

  .mp-chat-day-sep {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 14px 0 10px;
    clear: both;
  }

  .mp-chat-day-sep span {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: #64748b;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: 4px 12px;
  }

  #chatbox.mp-chat .mp-chat-bubble > .receiver,
  #chatbox.mp-chat .mp-chat-bubble > .sender {
    position: relative !important;
    display: inline-block !important;
    float: none !important;
    width: max-content !important;
    max-width: min(70%, 280px) !important;
    min-width: 0 !important;
    padding: 8px 12px 18px !important;
  }

  #chatbox.mp-chat .mp-chat-bubble .message-text {
    display: block !important;
    width: max-content !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    white-space: pre-wrap !important;
  }

  #chatbox.mp-chat .mp-chat-bubble .message-time {
    position: absolute !important;
    right: 10px !important;
    bottom: 5px !important;
    margin: 0 !important;
    float: none !important;
    display: block !important;
    white-space: nowrap !important;
  }

  #chatbox.mp-chat .receiver,
  #chatbox.mp-chat .sender {
    float: none !important;
  }

  .message-text {
    margin: 0 !important;
    padding: 5px !important;
    word-wrap: break-word;
    font-weight: 200;
    font-size: 14px;
    padding-bottom: 0 !important;
  }

  .shared-product-card {
    display: flex;
    gap: 8px;
    align-items: center;
  }

  .shared-product-thumb {
    width: 36px;
    height: 36px;
    object-fit: cover;
    border-radius: 4px;
  }

  .shared-product-title {
    font-weight: 600;
    font-size: 12px;
  }

  .shared-product-price {
    color: #f97316;
    font-size: 12px;
  }

  .shared-product-link {
    font-size: 11px;
    text-decoration: underline;
  }

  .message-main-receiver .message-time {
    margin: 0 !important;
    margin-left: 9px !important;
    font-size: 0.8em;
    /*text-align: right;*/
    color: #9a9a9a;
  }

  .message-main-sender .message-time {
    float: right;
    margin: 9px 9px 0 0 !important;
    font-size: 0.8em;
    /*text-align: right;*/
    color: #9a9a9a;
  }

  .receiver {
    width: auto !important;
    padding: 4px 10px 7px !important;
    border-radius: 10px 10px 10px 0;
    background: #ffffff;
    font-size: 12px;
    text-shadow: 0 1px 1px rgba(0, 0, 0, .2);
    word-wrap: break-word;
    display: inline-block;
  }

  .sender {
    float: right;
    width: auto !important;
    background: #dcf8c6;
    border-radius: 10px 10px 0 10px;
    padding: 4px 10px 7px !important;
    font-size: 12px;
    text-shadow: 0 1px 1px rgba(0, 0, 0, .2);
    display: inline-block;
    word-wrap: break-word;
  }

  /*Reply*/
  #chatbox .chat-attachment-preview {
    display: none;
    clear: both;
    width: 100%;
    box-sizing: border-box;
    background: #f0f4f8;
    border-top: 1px solid #dde3ea;
    border-left: 3px solid #009688;
    padding: 8px 10px;
    margin: 0;
  }

  #chatbox .chat-attachment-preview.chat-attachment-preview--visible {
    display: block;
  }

  #chatbox .chat-attachment-preview-inner {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  #chatbox .chat-attachment-preview-label {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #666;
  }

  #chatbox .chat-attachment-preview-row {
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 100%;
  }

  #chatbox .chat-attachment-preview-img {
    display: none;
    width: 44px;
    height: 44px;
    border-radius: 4px;
    object-fit: cover;
    flex-shrink: 0;
    border: 1px solid #ccc;
    background: #fff;
  }

  #chatbox .chat-attachment-preview-icon {
    display: none;
    flex-shrink: 0;
    width: 44px;
    text-align: center;
    color: #009688;
    font-size: 26px;
    line-height: 44px;
  }

  #chatbox .chat-attachment-preview-name {
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

  #chatbox .chat-attachment-preview-remove {
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

  #chatbox .chat-attachment-preview-remove:hover {
    background: #eee;
    color: #111;
  }

  .reply {
    min-height: 60px;
    height: auto;
    width: 100%;
    background-color: #f5f1ee;
    padding: 10px 5px 10px 5px !important;
    margin: 0 !important;
    z-index: 1000;
  }

  .reply i {
    text-align: center;
    padding: 5px !important;
    color: #93918f;
    cursor: pointer;
  }

  .reply-main {
    padding: 2px 0px !important;
  }

  .reply-main textarea {
    width: 100%;
    resize: none;
    overflow: hidden;
    padding: 8px !important;
    outline: none;
    border: none;
    text-indent: 5px;
    box-shadow: none;
    height: 100%;
    font-size: 16px;
  }

  .reply-main textarea:focus {
    outline: none;
    border: none;
    text-indent: 5px;
    box-shadow: none;
  }

  @media screen and (max-width: 700px) {
    #chatbox {
      top: 0;
      height: 100%;
    }

    .heading {
      height: 70px;
      background-color: #009688;
    }

    .fa-2x {
      font-size: 2.3em !important;
    }

    .heading-avatar {
      padding: 0 !important;
    }

    .heading-avatar img {
      height: 50px;
      width: 50px;
    }

    .heading-compose {
      padding: 5px !important;
    }

    .heading-compose i {
      color: #fff;
      cursor: pointer;
    }

    .heading-dot {
      padding: 5px !important;
      margin-left: 10px !important;
    }

    .heading-dot i {
      color: #fff;
      cursor: pointer;
    }

    .sidebarContent {
      height: calc(100% - 130px);
    }

    .sidebarBody {
      height: 80px;
    }

    .sidebarContent img {
      height: 55px;
      width: 55px;
    }

    .sideBar-main .row {
      padding: 0 !important;
      margin: 0 !important;
    }

    .sideBar-name {
      padding: 10px 5px !important;
    }

    .name-meta {
      font-size: 16px;
      padding: 5% !important;
    }

    .time-meta {
      text-align: right;
      font-size: 12px;
      padding: 4% !important;
      color: rgba(0, 0, 0, .4);
      vertical-align: baseline;
    }

    /*Conversation*/
    .conversation {
      padding: 0 !important;
      margin: 0 !important;
      height: 100%;
      /*width: 100%;*/
      border-left: 1px solid rgba(0, 0, 0, .08);
      /*overflow-y: auto;*/
    }

    .message {
      height: calc(100% - 140px);
    }

    .reply {
      height: 70px;
    }

    .reply i {
      padding: 5px 2px !important;
      font-size: 1.8em !important;
    }

    .reply-main {
      padding: 2px 8px !important;
    }

    .reply-main textarea {
      padding: 8px !important;
      font-size: 18px;
    }

    .reply-send i {
      padding: 5px 2px 5px 0 !important;
      font-size: 1.8em !important;
    }
  }

  /* Shared conversation list (admin + merchant markup) */
  #chatbox:not(.mp-chat) .mp-chat-list {
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  #chatbox:not(.mp-chat) .mp-chat-list__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #eee;
    height: 60px;
  }

  #chatbox:not(.mp-chat) .mp-chat-list__body {
    flex: 1;
    overflow-y: auto;
  }

  #chatbox:not(.mp-chat) .mp-chat-list__link {
    display: flex;
    gap: 12px;
    padding: 10px 12px;
    color: #666;
    text-decoration: none;
  }

  #chatbox:not(.mp-chat) .mp-chat-list__avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
  }

  #chatbox:not(.mp-chat) .mp-chat-list__meta {
    flex: 1;
    min-width: 0;
  }

  #chatbox:not(.mp-chat) .mp-chat-list__row {
    display: flex;
    justify-content: space-between;
    gap: 8px;
  }

  #chatbox:not(.mp-chat) .mp-chat-list__item.active,
  #chatbox:not(.mp-chat) .mp-chat-list__item:hover {
    background: #f5f5f5;
  }

  #chatbox:not(.mp-chat) .mp-chat-thread__head {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    height: 60px;
    background: #eee;
  }

  #chatbox:not(.mp-chat) .mp-chat-thread__peer {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  #chatbox:not(.mp-chat) .mp-chat-thread__avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
  }

  #chatbox:not(.mp-chat) .mp-chat-composer__form {
    display: flex;
    align-items: center;
    width: 100%;
  }

  #chatbox:not(.mp-chat) .mp-chat-composer__attach input {
    display: none;
  }

  #chatbox:not(.mp-chat) .mp-chat-composer__send {
    border: 0;
    background: transparent;
    color: #42a5f5;
    cursor: pointer;
  }
</style>
