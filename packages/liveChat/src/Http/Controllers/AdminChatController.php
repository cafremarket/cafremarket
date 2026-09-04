<?php

namespace Incevio\Package\LiveChat\Http\Controllers;

use App\Events\Chat\NewMessageEvent;
use App\Http\Controllers\Controller;
use App\Services\ChatSocketPublisher;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Incevio\Package\LiveChat\Http\Requests\SaveChatConversationRequest;
use Incevio\Package\LiveChat\Http\Requests\ViewChatConversationRequest;
use Incevio\Package\LiveChat\Models\ChatConversation;

class AdminChatController extends Controller
{
    /**
     * List shop chat conversations.
     */
    public function index(Request $request)
    {
        Gate::authorize('index', ChatConversation::class);

        $chats = ChatConversation::mine()
            ->with('customer')
            ->latest('updated_at')
            ->get();

        if (livechat_is_merchant_panel()) {
            return view('liveChat::merchant.index', compact('chats'));
        }

        return view('liveChat::index', compact('chats'));
    }

    /**
     * Display a conversation thread (AJAX partial).
     */
    public function show(ViewChatConversationRequest $request, ChatConversation $chat)
    {
        $chat->markAsRead();
        $chat->markPeerRepliesAsRead('merchant');

        $chat->loadMissing(['replies.attachments']);

        if (livechat_is_merchant_panel()) {
            return view('liveChat::merchant._conversation', compact('chat'));
        }

        return view('liveChat::_chat_conversation', compact('chat'));
    }

    /**
     * Store a merchant/admin reply.
     */
    public function reply(SaveChatConversationRequest $request, ChatConversation $chat)
    {
        try {
            Gate::authorize('reply', ChatConversation::class);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Not allowed to reply'], 403);
        }

        $shopId = Auth::user()?->merchantId();
        if ($shopId && (int) $chat->shop_id !== (int) $shopId) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        $replyText = trim((string) $request->input('message', ''));
        if ($replyText === '' && ($request->hasFile('photo') || $request->filled('photo'))) {
            $replyText = livechat_message_for_attachment_only();
        }

        if ($replyText === '' && ! $request->hasFile('photo') && ! $request->filled('photo')) {
            return response()->json(['message' => 'Empty message'], 422);
        }

        $userId = $request->input('user_id') ?: Auth::id();

        $reply = $chat->replies()->create([
            'customer_id' => null,
            'user_id' => $userId,
            'reply' => $replyText,
            'read' => false,
        ]);

        $chat->bumpLastMessage($replyText, false);

        if ($request->hasFile('photo')) {
            $reply->saveAttachments($request->file('photo'));
        } elseif ($request->filled('photo')) {
            $reply->saveAttachments(create_file_from_base64($request->get('photo')));
        }

        $attachmentsPayload = livechat_socket_attachments_payload($reply);

        $clock = livechat_format_message_time($reply->created_at);
        $createdAt = optional($reply->created_at)->toIso8601String();

        $payload = [
            'text' => $replyText,
            'sender_type' => 'merchant',
            'conversation_id' => $chat->id,
            'reply_id' => $reply->id,
            'customer_id' => $chat->customer_id,
            'time' => $clock,
            'created_at' => $createdAt,
            'attachments' => $attachmentsPayload,
        ];

        $chat->loadMissing('shop');

        try {
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
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            event(new NewMessageEvent($reply, $replyText));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => $replyText,
            'reply_id' => $reply->id,
            'time' => $clock,
            'created_at' => $createdAt,
            'attachments' => $attachmentsPayload,
            'ok' => true,
        ], 200);
    }
}
