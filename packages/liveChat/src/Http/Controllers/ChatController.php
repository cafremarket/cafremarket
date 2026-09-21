<?php

namespace Incevio\Package\LiveChat\Http\Controllers;

use App\Models\Shop;
use App\Models\Reply;
use App\Events\Chat\NewMessageEvent;
use App\Services\ChatSocketPublisher;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Incevio\Package\LiveChat\Http\Requests\ChatConversationRequest;
use Incevio\Package\LiveChat\Http\Requests\SaveChatConversationRequest;
use Incevio\Package\LiveChat\Models\ChatConversation;


// use Illuminate\Broadcasting\InteractsWithSockets;
// use App\Http\Requests\Validations\OrderDetailRequest;

class ChatController extends Controller
{
    /**
     * Resolve the `type`/`payload` to persist for an incoming message,
     * preferring explicit request fields and falling back to inferring
     * an attachment-only message the same way the body placeholder does.
     * Mirrors Api\ConversationController::resolveIncomingType().
     *
     * @return array{type: string, payload: array<string, mixed>|null}
     */
    protected function resolveIncomingType(\Illuminate\Http\Request $request, string $replyText): array
    {
        $type = $request->input('type');
        $payload = $request->input('payload');
        $payload = is_array($payload) ? $payload : null;

        if ($type) {
            return ['type' => $type, 'payload' => $payload];
        }

        if ($replyText === livechat_message_for_attachment_only()) {
            return ['type' => Reply::TYPE_ATTACHMENT, 'payload' => null];
        }

        return ['type' => Reply::TYPE_TEXT, 'payload' => null];
    }

    /**
     * Load an existing shop↔customer conversation for the storefront widget.
     * Returns null (200) when no thread exists yet — frontend shows the welcome prompt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|string  $shop  Shop id or slug (resolved manually so missing shops return JSON, not an HTML 404)
     *
     * @return \Illuminate\Http\Response
     */
    public function conversation(ChatConversationRequest $request, $shop)
    {
        $shopModel = $this->resolveShop($shop);

        if (! $shopModel) {
            return response()->json([
                'message' => trans('theme.chat_not_found'),
                'code' => 'chat_not_found',
            ], 404);
        }

        if (! Schema::hasTable('chat_conversations')) {
            return response()->json(null);
        }

        $conversation = ChatConversation::where([
            'customer_id' => Auth::guard('customer')->id(),
            'shop_id' => $shopModel->id,
        ])->with(livechat_replies_eager_load())->first();

        if ($conversation) {
            try {
                $conversation->markPeerRepliesAsRead('customer');
            } catch (\Throwable $e) {
                report($e);
            }

            try {
                $conversation->load(livechat_replies_eager_load());
            } catch (\Throwable $e) {
                report($e);
                $conversation->loadMissing(['replies.attachments']);
            }
        }

        return response()->json($conversation);
    }

    /**
     * Resolve shop by id or slug without throwing ModelNotFoundException.
     */
    protected function resolveShop($shop): ?Shop
    {
        $key = is_scalar($shop) ? trim((string) $shop) : '';
        if ($key === '') {
            return null;
        }

        return Shop::query()
            ->where(function ($q) use ($key) {
                if (ctype_digit($key)) {
                    $q->where('id', (int) $key);
                }
                $q->orWhere('slug', $key);
            })
            ->first();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function save(SaveChatConversationRequest $request)
    {
        $shop = Shop::where('slug', $request->shop_slug)->first();

        if (!$shop) {
            return response(trans('responses.404'), 404);
        }

        $replyText = trim((string) ($request->input('message') ?? ''));
        if ($replyText === '' && ($request->hasFile('photo') || $request->filled('photo'))) {
            $replyText = livechat_message_for_attachment_only();
        }

        if ($replyText === '' && ! $request->hasFile('photo') && ! $request->filled('photo')) {
            return response()->json(['message' => trans('validation.required', ['attribute' => 'message'])], 422);
        }

        if (! Schema::hasTable('chat_conversations')) {
            return response()->json(['message' => trans('api.something_went_wrong')], 503);
        }

        $conversation = ChatConversation::where([
            'customer_id' => $request->customer_id,
            'shop_id' => $shop->id
        ])->first();

        $quotedParent = $conversation
            ? Reply::resolveQuotedParent($conversation, $request)
            : null;

        $resolvedType = $this->resolveIncomingType($request, $replyText);

        if ($conversation) {
            $conversation->bumpLastMessage($replyText, true);
            $createAttrs = [
                'customer_id' => $request->customer_id,
                'user_id' => $request->user_id,
                'reply' => $replyText,
                'read' => false,
                'type' => $resolvedType['type'],
                'payload' => $resolvedType['payload'],
            ];
            if ($quotedParent) {
                $createAttrs['parent_id'] = $quotedParent->id;
            }
            $msg_object = $conversation->replies()->create($createAttrs);
        } elseif ($request->customer_id) {
            $conversation = ChatConversation::create([
                'shop_id' => $shop->id,
                'customer_id' => $request->customer_id,
                'message' => $replyText,
                'status' => ChatConversation::STATUS_NEW,
            ]);

            $createAttrs = [
                'customer_id' => $request->customer_id,
                'user_id' => $request->user_id,
                'reply' => $replyText,
                'read' => false,
                'type' => $resolvedType['type'],
                'payload' => $resolvedType['payload'],
            ];
            if ($quotedParent) {
                $createAttrs['parent_id'] = $quotedParent->id;
            }
            $msg_object = $conversation->replies()->create($createAttrs);
        } else {
            return response(trans('responses.unauthorized'), 401);
        }

        if ($request->hasFile('photo')) {
            $msg_object->saveAttachments($request->file('photo'));
        } elseif ($request->filled('photo')) {
            $msg_object->saveAttachments(create_file_from_base64($request->get('photo')));
        }

        $attachmentsPayload = livechat_socket_attachments_payload($msg_object);
        $conversation->refresh();

        $room = get_chat_room_name($shop->id.$request->customer_id);
        $clock = livechat_format_message_time($msg_object->created_at);
        $socketPayload = array_merge([
            'text' => $replyText,
            'sender_type' => 'customer',
            'conversation_id' => $conversation->id,
            'reply_id' => $msg_object->id,
            'customer_id' => $request->customer_id,
            'time' => $clock,
            'created_at' => optional($msg_object->created_at)->toIso8601String(),
            'attachments' => $attachmentsPayload,
            'type' => $resolvedType['type'],
            'payload' => $resolvedType['payload'],
        ], livechat_quote_socket_payload($quotedParent));

        ChatSocketPublisher::publish($room, 'chat.message', $socketPayload);

        ChatSocketPublisher::publish(get_vendor_chat_room_id($shop), 'chat.message', $socketPayload);

        try {
            event(new NewMessageEvent($msg_object, $replyText));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'status' => 'ok',
            'conversation_id' => $conversation->id,
            'reply_id' => $msg_object->id,
            'time' => $clock,
            'created_at' => optional($msg_object->created_at)->toIso8601String(),
            'attachments' => $attachmentsPayload,
            'parent_id' => $quotedParent?->id,
            'quoted_reply' => Reply::quoteSnapshot($quotedParent),
            'type' => $resolvedType['type'],
            'payload' => $resolvedType['payload'],
        ], 200);
    }
}
