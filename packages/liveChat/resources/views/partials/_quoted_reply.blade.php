@if (!empty($quoted) && is_array($quoted))
  <button type="button" class="chat-quote" data-parent-id="{{ $quoted['id'] ?? '' }}">
    <strong>{{ $quoted['sender_name'] ?? '' }}</strong>
    <span>{{ $quoted['reply'] ?? '' }}</span>
  </button>
@endif
