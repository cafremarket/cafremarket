<?php

namespace Incevio\Package\LiveChat\Http\Controllers;

use App\Events\Chat\NewMessageEvent;
use App\Http\Controllers\Controller;
use App\Models\Reply;
use App\Services\ChatSocketPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Incevio\Package\LiveChat\Http\Requests\SaveChatConversationRequest;
use Incevio\Package\LiveChat\Models\ChatConversation;

class CustomerChatController extends Controller
{
    /**
     * Display a conversation thread (AJAX partial) for the logged-in customer.
     */
    public function show(Request $request, ChatConversation $chat)
    {
        $this->authorizeCustomer($chat);

        try {
            $chat->markPeerRepliesAsRead('customer');
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $with = array_merge(
                function_exists('livechat_replies_eager_load')
                    ? livechat_replies_eager_load()
                    : ['replies.attachments'],
                ['shop.logo', 'shop', 'order']
            );
            $chat->loadMissing($with);
        } catch (\Throwable $e) {
            report($e);
            $chat->loadMissing(['replies.attachments', 'shop']);
        }

        return view('liveChat::customer._conversation', compact('chat'));
    }

    /**
     * Store a customer reply on an existing conversation.
     */
    public function reply(SaveChatConversationRequest $request, ChatConversation $chat)
    {
        $this->authorizeCustomer($chat);

        $customerId = Auth::guard('customer')->id();
        $replyText = trim((string) $request->input('message', ''));

        if ($replyText === '' && ($request->hasFile('photo') || $request->filled('photo'))) {
            $replyText = livechat_message_for_attachment_only();
        }

        if ($replyText === '' && ! $request->hasFile('photo') && ! $request->filled('photo')) {
            return response()->json(['message' => trans('validation.required', ['attribute' => 'message'])], 422);
        }

        $quotedParent = Reply::resolveQuotedParent($chat, $request);

        $type = $request->input('type');
        $payload = $request->input('payload');
        $payload = is_array($payload) ? $payload : null;
        if (! $type) {
            $type = $replyText === livechat_message_for_attachment_only()
                ? Reply::TYPE_ATTACHMENT
                : Reply::TYPE_TEXT;
        }

        $createAttrs = [
            'customer_id' => $customerId,
            'user_id' => null,
            'reply' => $replyText,
            'read' => false,
            'type' => $type,
            'payload' => $payload,
        ];
        if ($quotedParent) {
            $createAttrs['parent_id'] = $quotedParent->id;
        }
        $reply = $chat->replies()->create($createAttrs);

        $chat->bumpLastMessage($replyText, true);

        if ($request->hasFile('photo')) {
            $reply->saveAttachments($request->file('photo'));
        } elseif ($request->filled('photo')) {
            $reply->saveAttachments(create_file_from_base64($request->get('photo')));
        }

        $attachmentsPayload = livechat_socket_attachments_payload($reply);
        $clock = livechat_format_message_time($reply->created_at);
        $createdAt = optional($reply->created_at)->toIso8601String();

        $socketPayload = array_merge([
            'text' => $replyText,
            'sender_type' => 'customer',
            'conversation_id' => $chat->id,
            'reply_id' => $reply->id,
            'customer_id' => $chat->customer_id,
            'shop_id' => $chat->shop_id,
            'time' => $clock,
            'created_at' => $createdAt,
            'attachments' => $attachmentsPayload,
            'type' => $reply->resolvedType(),
            'payload' => $reply->resolvedPayload(),
        ], livechat_quote_socket_payload($quotedParent));

        $chat->loadMissing('shop');

        try {
            ChatSocketPublisher::publish(
                get_chat_room_name($chat->shop_id.$chat->customer_id),
                'chat.message',
                $socketPayload
            );

            if ($chat->shop) {
                ChatSocketPublisher::publish(
                    get_vendor_chat_room_id($chat->shop),
                    'chat.message',
                    $socketPayload
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
            'parent_id' => $quotedParent?->id,
            'quoted_reply' => Reply::quoteSnapshot($quotedParent),
            'type' => $reply->resolvedType(),
            'payload' => $reply->resolvedPayload(),
            'ok' => true,
        ], 200);
    }

    protected function authorizeCustomer(ChatConversation $chat): void
    {
        $customerId = Auth::guard('customer')->id();

        if (! $customerId || (int) $chat->customer_id !== (int) $customerId) {
            abort(404, trans('theme.chat_not_found'));
        }
    }
}
