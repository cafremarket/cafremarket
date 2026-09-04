<style type="text/css">
  #zcart_chat {
    --lc-accent: var(--primary-color, #ff6600);
    --lc-accent-dark: var(--primary-dark, #cc5200);
    --lc-surface: #ffffff;
    --lc-ink: #1e293b;
    --lc-muted: #64748b;
    --lc-line: #e2e8f0;
    --lc-soft: #f8fafc;
    bottom: 20px;
    right: 20px;
    position: fixed;
    z-index: 998;
    font-family: inherit;
  }

  #zcart_chat ul li {
    list-style: none;
  }

  #zcart_chat .sf-livechat-fab.fchat {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    color: #fff;
    margin: 0;
    box-shadow: 0 10px 28px rgba(255, 102, 0, 0.35);
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    position: relative;
    z-index: 999;
    overflow: hidden;
    background: linear-gradient(145deg, var(--lc-accent) 0%, var(--lc-accent-dark) 100%);
    text-decoration: none;
  }

  #zcart_chat .sf-livechat-fab.fchat:hover {
    transform: translateY(-2px) scale(1.04);
    box-shadow: 0 14px 32px rgba(255, 102, 0, 0.42);
  }

  #zcart_chat .sf-livechat-fab.fchat > i {
    font-size: 1.55rem;
    line-height: 1;
    transition: transform 0.2s ease;
  }

  #zcart_chat .sf-livechat-fab.fchat.is-float {
    background: #0f172a;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.28);
  }

  #zcart_chat .chat {
    display: none;
    position: absolute;
    right: 0;
    bottom: 78px;
    width: min(380px, calc(100vw - 28px));
    height: min(560px, calc(100vh - 120px));
    background: var(--lc-surface);
    border-radius: 20px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
    overflow: hidden;
    flex-direction: column;
  }

  #zcart_chat .chat.is-visible {
    display: flex;
    animation: sfLivechatIn 0.22s ease;
  }

  @keyframes sfLivechatIn {
    from { opacity: 0; transform: translateY(10px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }

  #zcart_chat .chat_header {
    background: linear-gradient(135deg, var(--lc-accent) 0%, var(--lc-accent-dark) 100%);
    color: #fff;
    padding: 14px 16px;
    flex-shrink: 0;
  }

  #zcart_chat .chat_option {
    display: flex;
    align-items: center;
    gap: 12px;
    float: none;
    width: auto;
  }

  #zcart_chat .header_img {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.55);
    background: rgba(255, 255, 255, 0.2);
    flex-shrink: 0;
    float: none;
  }

  #zcart_chat .header_img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  #zcart_chat .chat_header_text {
    flex: 1;
    min-width: 0;
  }

  #zcart_chat #chat_head {
    display: block;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  #zcart_chat .chat_option .agent {
    display: block;
    font-size: 12px;
    opacity: 0.92;
    margin-top: 2px;
  }

  #zcart_chat .chat_option .online {
    opacity: 0.85;
  }

  #zcart_chat .chat_header_close {
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    flex-shrink: 0;
  }

  #zcart_chat .chat_header_close:hover {
    background: rgba(255, 255, 255, 0.28);
  }

  #zcart_chat #chat_conversation,
  #zcart_chat .chat_converse {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background:
      radial-gradient(circle at top left, rgba(255, 102, 0, 0.06), transparent 42%),
      var(--lc-soft);
    margin: 0;
    height: auto;
  }

  #zcart_chat .chat_login_prompt,
  #zcart_chat .chat_connecting {
    text-align: center;
    color: var(--lc-muted);
    font-size: 14px;
    line-height: 1.5;
    margin: 28px 8px;
  }

  #zcart_chat .chat_login_btn.btn {
    display: inline-block;
    margin: 12px auto 0;
    background: var(--lc-accent);
    border-color: var(--lc-accent);
    border-radius: 999px;
    padding: 8px 18px;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item {
    position: relative;
    margin: 0 0 14px;
    padding: 10px 12px 22px;
    max-width: 82%;
    clear: both;
    word-wrap: break-word;
    font-size: 14px;
    line-height: 1.45;
    border-radius: 16px;
    box-shadow: none;
  }

  #zcart_chat .chat-day-sep {
    display: flex;
    align-items: center;
    justify-content: center;
    clear: both;
    margin: 10px 0 14px;
  }

  #zcart_chat .chat-day-sep span {
    font-size: 11px;
    font-weight: 600;
    color: var(--lc-muted);
    background: #eef2f7;
    border-radius: 999px;
    padding: 4px 12px;
  }

  #zcart_chat .chat-msg-time {
    display: block;
    position: absolute;
    right: 10px;
    bottom: 5px;
    font-size: 10px;
    line-height: 1;
    opacity: 0.72;
    font-weight: 500;
  }

  #zcart_chat .chat_msg_item_admin .chat-msg-time {
    color: var(--lc-muted);
  }

  #zcart_chat .chat_msg_item_user .chat-msg-time {
    color: rgba(255, 255, 255, 0.85);
  }

  #zcart_chat .chat .chat_converse .chat_msg_item.chat_msg_item_admin {
    float: left;
    background: #fff;
    color: var(--lc-ink);
    border: 1px solid var(--lc-line);
    border-bottom-left-radius: 6px;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item.chat_msg_item_user {
    float: right;
    background: linear-gradient(135deg, var(--lc-accent), var(--lc-accent-dark));
    color: #fff;
    border-bottom-right-radius: 6px;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item.chat_msg_item_admin:before {
    display: none;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item .chat_avatar {
    position: absolute;
    bottom: 0;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item.chat_msg_item_admin .chat_avatar {
    left: -42px;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item.chat_msg_item_user .chat_avatar {
    right: -42px;
  }

  #zcart_chat .chat .chat_converse .chat_msg_item .chat_avatar,
  #zcart_chat .chat_avatar img {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
  }

  #zcart_chat strong.chat_time {
    display: block;
    margin-top: 6px;
    font-size: 11px;
    font-weight: 500;
    opacity: 0.7;
  }

  #zcart_chat .chat-product-share {
    padding: 10px 12px 0;
    background: #fff;
    border-top: 1px solid var(--lc-line);
    flex-shrink: 0;
  }

  #zcart_chat .chat-product-share-title {
    font-size: 11px;
    font-weight: 600;
    color: var(--lc-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 8px;
  }

  #zcart_chat .chat-product-share-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border: 1px solid var(--lc-line);
    border-radius: 12px;
    background: var(--lc-soft);
  }

  #zcart_chat .chat-product-share-media img {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    object-fit: cover;
  }

  #zcart_chat .chat-product-share-body {
    flex: 1;
    min-width: 0;
  }

  #zcart_chat .chat-product-share-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--lc-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  #zcart_chat .chat-product-share-price {
    font-size: 12px;
    color: var(--lc-accent);
    font-weight: 600;
  }

  #zcart_chat .chat-product-share-actions {
    display: flex;
    align-items: center;
    gap: 4px;
  }

  #zcart_chat .chat-product-share-btn {
    border: 0;
    background: var(--lc-accent);
    color: #fff;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
  }

  #zcart_chat .chat-product-share-dismiss {
    border: 0;
    background: transparent;
    color: var(--lc-muted);
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    width: 28px;
    height: 28px;
  }

  #zcart_chat .fchat_field.chat-composer {
    padding: 12px;
    background: #fff;
    border-top: 1px solid var(--lc-line);
    flex-shrink: 0;
    position: relative;
    bottom: auto;
  }

  #zcart_chat .chat-composer-inner {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  #zcart_chat .chat-composer-row {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--lc-soft);
    border: 1px solid var(--lc-line);
    border-radius: 999px;
    padding: 4px;
  }

  #zcart_chat .chat-composer-btn {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 50%;
    background: transparent;
    color: var(--lc-muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    padding: 0;
    margin: 0;
  }

  #zcart_chat label.chat-composer-btn {
    margin: 0;
  }

  #zcart_chat .chat-composer-btn--send {
    background: var(--lc-accent);
    color: #fff;
  }

  #zcart_chat .chat-composer-btn--send:hover {
    background: var(--lc-accent-dark);
  }

  #zcart_chat .chat-composer-file-input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    overflow: hidden;
  }

  #zcart_chat .chat-composer-msg,
  #zcart_chat .chat_field.chat_message {
    flex: 1;
    min-width: 0;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    outline: none !important;
    height: 38px;
    padding: 0 4px;
    font-size: 14px;
    color: var(--lc-ink);
    width: auto;
  }

  #zcart_chat .chat-sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
  }

  #zcart_chat .chat-attachment-preview {
    display: none;
  }

  #zcart_chat .chat-attachment-preview.chat-attachment-preview--visible,
  #zcart_chat .chat-attachment-preview[aria-hidden="false"] {
    display: block;
  }

  #zcart_chat .chat-attachment-preview-inner {
    background: var(--lc-soft);
    border: 1px solid var(--lc-line);
    border-radius: 12px;
    padding: 8px 10px;
  }

  #zcart_chat .chat-attachment-preview-label {
    display: block;
    font-size: 11px;
    color: var(--lc-muted);
    margin-bottom: 6px;
  }

  #zcart_chat .chat-attachment-preview-row {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  #zcart_chat .chat-attachment-preview-img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 8px;
  }

  #zcart_chat .chat-attachment-preview-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--lc-muted);
  }

  #zcart_chat .chat-attachment-preview-name {
    flex: 1;
    min-width: 0;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  #zcart_chat .chat-attachment-preview-remove {
    border: 0;
    background: transparent;
    font-size: 18px;
    color: var(--lc-muted);
    cursor: pointer;
  }

  #zcart_chat .chat-shared-product-wrap {
    clear: both;
    margin: 0 0 12px;
  }

  #zcart_chat .chat-shared-product {
    display: flex;
    gap: 10px;
    align-items: center;
    background: #fff;
    border: 1px solid var(--lc-line);
    border-radius: 12px;
    padding: 8px;
    max-width: 86%;
  }

  #zcart_chat .chat-shared-product-img {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
  }

  #zcart_chat .chat-shared-product-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--lc-ink);
  }

  #zcart_chat .chat-shared-product-price {
    font-size: 12px;
    color: var(--lc-accent);
    font-weight: 600;
  }

  #zcart_chat .chat-shared-product-link {
    font-size: 12px;
    color: var(--lc-accent);
  }

  #zcart_chat .chat-attachment-block {
    margin-top: 8px;
  }

  #zcart_chat .chat-att-thumb {
    max-width: 180px;
    border-radius: 10px;
    display: block;
  }

  #zcart_chat .chat-att-link {
    color: inherit;
    text-decoration: underline;
    font-size: 12px;
  }

  #zcart_chat .status {
    clear: both;
    text-align: center;
    color: var(--lc-muted);
    font-size: 12px;
    margin: 8px 0;
  }

  #zcart_chat .is-active {
    transform: rotate(0deg);
  }

  @media (max-width: 480px) {
    #zcart_chat {
      right: 12px;
      bottom: 12px;
    }

    #zcart_chat .chat {
      width: calc(100vw - 24px);
      height: min(72vh, 560px);
      bottom: 72px;
      border-radius: 16px;
    }
  }
</style>
