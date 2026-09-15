{{-- Merchant order page: chat modal using existing LiveChat (shop↔customer). --}}
<div class="modal-dialog modal-lg modal-dialog-centered">
  <div class="modal-content mpc-order-chat-modal">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      <h4 class="modal-title">
        <i class="fa fa-comments"></i>
        {{ trans('app.send_message') }}
        @if ($order->order_number)
          <small class="text-muted">· #{{ $order->order_number }}</small>
        @endif
      </h4>
    </div>

    <div class="modal-body nopadding" id="mpc-order-chat-root"
         data-csrf="{{ csrf_token() }}"
         data-order-id="{{ $order->id }}"
         data-conversation-id="{{ $chat->id }}">
      <div class="mpc mpc--order-modal">
        <section class="mpc__thread mpc__thread--modal" id="chatConversation">
          @include('liveChat::merchant._conversation', [
            'chat' => $chat,
            'hideBackButton' => true,
            'orderContextNumber' => $order->order_number,
          ])
        </section>
      </div>
    </div>
  </div>
</div>

<style>
  .mpc-order-chat-modal .modal-body { padding: 0; }
  .mpc-order-chat-modal .mpc--order-modal {
    min-height: 420px;
    max-height: min(70vh, 640px);
    display: flex;
    flex-direction: column;
  }
  .mpc-order-chat-modal .mpc__thread--modal {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    height: min(70vh, 640px);
  }
  .mpc-order-chat-modal .mpc-thread__messages {
    flex: 1;
    overflow-y: auto;
    min-height: 220px;
  }
  .mpc-order-chat-modal .mpc-composer {
    border-top: 1px solid #e5e7eb;
    background: #fff;
  }
</style>
