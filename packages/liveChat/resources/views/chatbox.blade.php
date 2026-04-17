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
      var link = '{{ route('admin.support.chat_conversation.show', ':slug') }}';
      link = link.replace(':slug', msgObj.conversation_id);

      return $('<div>').attr('id', 'chat-' + msgObj.customer_id).addClass('row sidebarBody').append(
        $('<a>').attr('href', 'javascript:void(0)').attr('data-link', link).addClass('get-content').append(
          $('<div>').addClass('col-sm-3 col-xs-3').append(
            $('<img/>').attr('src', msgObj.avatar).attr('alt', '{{ trans('app.avatar') }}').addClass('img-circle')
          )
        ).append(
          $('<div>').addClass('col-sm-9 col-xs-9 sideBar-main nopadding').append(
            $('<div>').addClass('row').append(
              $('<div>').addClass('col-sm-8 col-xs-8 sideBar-name').append(
                $('<span>').addClass('name-meta strong').text(msgObj.sender).append(
                  $('<span>').addClass('label label-primary flat indent10').text(msgObj.status)
                )
              ).append(
                $('<p>').addClass('excerpt strong').text(getExcerptMsg(msgObj.text, msgObj.attachments))
              )
            ).append(
              $('<div>').addClass('col-sm-4 col-xs-4 pull-right time').append(
                $('<span>').addClass('time-meta pull-right').text(msgObj.time)
              )
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

    function prepareNewChatMsg(txt, who, attachments) {
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

      return $('<div>').addClass('row message-body').append(
        $('<div>').addClass('col-sm-12 message-main-' + who).append(
          $('<div>').addClass(who).append(
            contentNode
          )
        ).append(
          $('<span>').addClass('message-time').text('{{ trans('theme.now') }}')
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

      $('body').on('click', 'i#send-btn', function(e) {
        e.preventDefault();

        var msg = $.trim($("textarea#message").val());
        var form = $(this).parents("form#chat-form");
        var fileInput = form.find('input[name="photo"]')[0];
        var hasFile = fileInput && fileInput.files && fileInput.files.length;
        if (msg === '' && !hasFile) {
          return;
        }

        var fdFile = hasFile && fileInput.files && fileInput.files.length ? fileInput.files[0] : null;
        var ajaxData = hasFile ? new FormData(form[0]) : form.serialize();

        if (hasFile && fdFile) {
          MerchantChatAttachmentPreview.clear();
        }

        var pendingNode = null;
        if (hasFile && fdFile) {
          var ext = (fdFile.name.split('.').pop() || '').toLowerCase();
          var pendingAtt = [{
            url: URL.createObjectURL(fdFile),
            name: fdFile.name,
            extension: ext || 'file'
          }];
          pendingNode = prepareNewChatMsg(msg || '[attachment]', 'sender', pendingAtt).attr('data-pending', '1');
          $("#conversationBox").append(pendingNode);
          updateScroll('conversationBox');
        }

        var response = '';

        var ajaxOpts = {
          url: form.attr('action'),
          type: 'POST',
          data: ajaxData,
          complete: function(xhr, textStatus) {
            $('#conversationBox [data-pending="1"]').remove();

            switch (xhr.status) {
              case 200: {
                $("textarea#message").val('');
                if (fileInput) {
                  fileInput.value = '';
                }
                var replyMsg = msg;
                var attachments = [];
                try {
                  var parsed = JSON.parse(xhr.responseText);
                  if (parsed && typeof parsed === 'object') {
                    if (parsed.message != null) {
                      replyMsg = parsed.message;
                    }
                    attachments = parsed.attachments || [];
                  }
                } catch (err) {
                  // Legacy non-JSON success body
                }
                response = prepareNewChatMsg(replyMsg || (hasFile ? '[attachment]' : ''), 'sender', attachments);

                var openChatbox = document.querySelector('[id^="openChatbox-"]');
                if (openChatbox && openChatbox.id) {
                  var customerId = openChatbox.id.replace('openChatbox-', '');
                  var chatNode = $('#chat-' + customerId);
                  if (chatNode.length) {
                    chatNode.find("p.excerpt").text(getExcerptMsg(replyMsg, attachments));
                    chatNode.find(".time span").text('{{ trans('theme.now') }}');
                  }
                }
                break;
              }
              case 401:
                MerchantChatAttachmentPreview.clear();
                $("#conversationBox").html(""); //Clear the chatbox
                response = $('<p>').addClass('text-danger').text("{!! trans('messages.session_expired') !!}");
                $('<br/><br/>').prependTo(response);
                $('<a>').attr('href', "javascript:void(0)").attr('data-toggle', "modal").attr('data-target', "#loginModal").addClass('btn btn-primary').text("{{ trans('app.login') }}").appendTo(response);
                break;
              case 403:
              case 419:
                MerchantChatAttachmentPreview.clear();
                $("#conversationBox").html(""); //Clear the chatbox
                response = $('<p>').addClass('text-danger').text("{!! trans('messages.session_expired') !!}");
                $('<br/><br/>').prependTo(response);
                $('<a>').attr('href', "{{ route('customer.login') }}").addClass('btn btn-primary').text("{{ trans('app.login') }}").appendTo(response);
                break;
              default:
                response = $('<div>').addClass('row message-body').append(
                  $('<div>').addClass('col-sm-12').append(
                    $('<p class="lead">').addClass('text-danger').text("{!! trans('messages.failed') !!}")
                  )
                );
                $('<br/><br/>').prependTo(response);
            }

            $("#conversationBox").append(response);

            updateScroll('conversationBox'); //Scroll to bottom
          },
        };
        if (hasFile) {
          ajaxOpts.processData = false;
          ajaxOpts.contentType = false;
        }
        $.ajax(ajaxOpts);
      });

      var wsScheme = '{{ config('chat_socket.scheme') }}';
      var wsHost = '{{ config('chat_socket.client_host') }}';
      var wsPort = '{{ (int) config('chat_socket.port') }}';
      var wsPath = '{{ trim((string) config('chat_socket.client_path', '')) }}';
      if (wsPath && wsPath.charAt(0) !== '/') {
        wsPath = '/' + wsPath;
      }
      var wsUrl = wsScheme + '://' + wsHost + (wsPath ? wsPath : (':' + wsPort));
      var room = '{{ get_vendor_chat_room_id() }}';
      var socket = null;

      function connectSocket() {
        try {
          socket = new WebSocket(wsUrl);
        } catch (e) {
          return;
        }

        socket.onopen = function() {
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
          if (result.sender_type !== 'customer') {
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
        } else { //Old customer
          var openCustomerId = customerId;
          if (!openCustomerId && chatNode.attr('id')) {
            openCustomerId = String(chatNode.attr('id')).replace('chat-', '');
          }
          var openChatbox = openCustomerId ? document.getElementById("openChatbox-" + openCustomerId) : null;
          if (openChatbox) { //The chatbox is already open
            response = prepareNewChatMsg(result.text, 'receiver', result.attachments || []);
            $("#conversationBox").append(response);
            updateScroll('conversationBox'); //Scroll to bottom
          } else { //Chatbox is not open
            markAsUnread(chatNode); // Mark as unread
          }

          chatNode.find("p.excerpt").text(getExcerptMsg(result.text, result.attachments)); // Update the excerpt on left menu
          chatNode.find(".time span").text(result.time || '{{ trans('theme.now') }}'); // Update the time on left menu
        }

        };

        socket.onclose = function() {
          setTimeout(connectSocket, 1200);
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

  .message-main-receiver {
    /*padding: 10px 20px;*/
    max-width: 60%;
  }

  .message-main-sender {
    padding: 3px 20px !important;
    margin-left: 40% !important;
    max-width: 60%;
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
</style>
