<div id="leftsidebar" class="mp-chat-list">
  <div class="mp-chat-list__head heading">
    <div class="heading-title">
      <i class="fa fa-comments" aria-hidden="true"></i>
      <span>{{ trans('app.chat_conversations') }}</span>
    </div>
    <span class="mp-chat-list__count">{{ $chats->count() }}</span>
  </div>

  <div class="mp-chat-list__body sidebarContent">
    @forelse($chats as $conversation)
      <div id="chat-{{ $conversation->customer_id }}" class="mp-chat-list__item sidebarBody {{ isset($chat) && $conversation->id == $chat->id ? 'active' : '' }} {{ $conversation->isUnread() ? 'is-unread' : '' }}">
        <a href="javascript:void(0)" data-link="{{ livechat_support_route('chat_conversation.show', $conversation) }}" class="get-content mp-chat-list__link">
          <img src="{{ get_avatar_src($conversation->customer, 'mini') }}" class="mp-chat-list__avatar img-circle" alt="{{ trans('app.avatar') }}">

          <div class="mp-chat-list__meta sideBar-main">
            <div class="mp-chat-list__row">
              <span class="name-meta {{ $conversation->isUnread() ? 'strong' : '' }}">
                {!! $conversation->customer->getName() !!}
              </span>
              <span class="time-meta">{{ $conversation->updated_at->diffForHumans() }}</span>
            </div>

            <div class="mp-chat-list__row mp-chat-list__row--sub">
              <p class="excerpt {{ $conversation->isUnread() ? 'strong' : '' }}">
                @php
                  $lastMessage = (string) $conversation->last_message();
                  $sharePrefix = '[product_share]';
                  if (str_starts_with($lastMessage, $sharePrefix)) {
                      $shared = json_decode(substr($lastMessage, strlen($sharePrefix)), true);
                      $preview = '[Product] '.($shared['title'] ?? 'Shared item');
                  } else {
                      $preview = $lastMessage;
                  }
                @endphp
                {!! \Illuminate\Support\Str::limit($preview, 80) !!}
              </p>
              <span class="mp-chat-list__badge label label-primary flat {{ !$conversation->isUnread() ? 'hide' : '' }}">{{ $conversation->statusName(true) }}</span>
            </div>
          </div>
        </a>
      </div>
    @empty
      <div class="mp-chat-list__empty">
        <i class="fa fa-inbox"></i>
        <p>{{ trans('app.no_data_found') }}</p>
      </div>
    @endforelse
  </div>
</div>
