<?php

namespace Incevio\Package\LiveChat\Http\Controllers;;

//use App\Common\Authorizable;
use App\Events\Chat\NewMessageEvent;
use App\Http\Controllers\Controller;
use App\Services\ChatSocketPublisher;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Incevio\Package\LiveChat\Http\Requests\SaveChatConversationRequest;
use Incevio\Package\LiveChat\Http\Requests\ViewChatConversationRequest;
use Incevio\Package\LiveChat\Models\ChatConversation;

class AdminChatController extends Controller
{
    //use Authorizable;

    /**
     * Show feedback form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Order   $order
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Gate::authorize('index', ChatConversation::class);
        $chats = \Incevio\Package\LiveChat\Models\ChatConversation::mine()->get();

        return view('liveChat::index', compact('chats'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Models\ChatConversation   $chat
     *
     * @return \Illuminate\Http\Response
     */
    public function show(ViewChatConversationRequest $request, ChatConversation $chat)
    {
        $chat->markAsRead();
        $chat->markPeerRepliesAsRead('merchant');

        $chat->loadMissing(['replies.attachments']);

        return view('liveChat::_chat_conversation', compact('chat'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function reply(SaveChatConversationRequest $request, ChatConversation $chat)
    {
        Gate::authorize('reply', ChatConversation::class);

        $replyText = trim((string) $request->input('message', ''));
        if ($replyText === '' && ($request->hasFile('photo') || $request->filled('photo'))) {
            $replyText = livechat_message_for_attachment_only();
        }

        $reply = $chat->replies()->create([
            'customer_id' => null,
            'user_id' => $request->user_id ?: Auth::id(),
            'reply' => $replyText,
            'read' => false,
        ]);

        if ($request->hasFile('photo')) {
            $reply->saveAttachments($request->file('photo'));
        } elseif ($request->filled('photo')) {
            $reply->saveAttachments(create_file_from_base64($request->get('photo')));
        }

        $attachmentsPayload = livechat_socket_attachments_payload($reply);

        $payload = [
            'text' => $replyText,
            'sender_type' => 'merchant',
            'conversation_id' => $chat->id,
            'reply_id' => $reply->id,
            'customer_id' => $chat->customer_id,
            'attachments' => $attachmentsPayload,
        ];

        // Realtime first — ChatSocketPublisher → chat-ws-node (apps/web).
        ChatSocketPublisher::publish(
            get_chat_room_name($chat->shop_id.$chat->customer_id),
            'chat.message',
            $payload
        );

        if ($chat->shop) {
            ChatSocketPublisher::publish(
                get_vendor_chat_room_id($chat->shop),
                'chat.message',
                $payload
            );
        }

        // Email/push notifications (queued listener — must not block reply).
        try {
            event(new NewMessageEvent($reply, $replyText));
        } catch (\Throwable $e) {
            report($e);
        }

        if ($request->ajax()) {
            return response()->json([
                'message' => $replyText,
                'reply_id' => $reply->id,
                'attachments' => $attachmentsPayload,
            ], 200);
        }

        return back();
    }
}
