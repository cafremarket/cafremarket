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
     *
     * First-time (never chatted): HTTP 200 + `{ status: 'empty', is_new: true }`
     * Existing thread: HTTP 200 + `{ status: 'ok', is_new: false, replies: [...] }`
     * Unknown shop: HTTP 404 + `{ code: 'chat_not_found' }`
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|string  $shop  Shop id or slug
     *
     * @return \Illuminate\Http\Response
     */
    public function conversation(ChatConversationRequest $request, $shop)
    {
        $shopModel = $this->resolveShop($shop);

        if (! $shopModel) {
            return response()->json([
                'status' => 'error',
                'code' => 'chat_not_found',
                'message' => trans('theme.chat_not_found'),
            ], 404)->header('Cache-Control', 'no-store, private');
        }

        $noCache = fn ($payload, int $code = 200) => response()
            ->json($payload, $code)
            ->header('Cache-Control', 'no-store, private');

        if (! Schema::hasTable('chat_conversations')) {
            return $noCache($this->emptyConversationPayload($shopModel));
        }

        $customerId = Auth::guard('customer')->id() ?: Auth::guard('api')->id();

        $conversation = ChatConversation::query()
            ->where('customer_id', $customerId)
            ->where('shop_id', $shopModel->id)
            ->latest('updated_at')
            ->latest('id')
            ->first();

        // First time — no thread yet. Not an error; widget shows welcome + composer.
        if (! $conversation) {
            return $noCache($this->emptyConversationPayload($shopModel));
        }

        try {
            $conversation->markPeerRepliesAsRead('customer');
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $rels = livechat_replies_eager_load();
            $conversation->load([
                'replies' => function ($q) {
                    $q->orderBy('id');
                },
                'replies.attachments',
            ]);
            if (in_array('replies.parent', $rels, true)) {
                $conversation->load('replies.parent');
            }
        } catch (\Throwable $e) {
            report($e);
            $conversation->loadMissing(['replies' => function ($q) {
                $q->orderBy('id');
            }, 'replies.attachments']);
        }

        return $noCache(array_merge(
            ['status' => 'ok', 'is_new' => false],
            $this->conversationPayload($conversation)
        ));
    }

    /**
     * Payload when customer and shop have never chatted.
     *
     * @return array<string, mixed>
     */
    protected function emptyConversationPayload(Shop $shop): array
    {
        return [
            'status' => 'empty',
            'is_new' => true,
            'id' => null,
            'shop_id' => (int) $shop->id,
            'replies' => [],
            'message' => null,
            'welcome' => trans('theme.chat_welcome'),
            'hint' => trans('theme.chat_start_hint'),
        ];
    }

    /**
     * Resolve shop by id or slug without throwing ModelNotFoundException.
     * Same lookup strategy as save() (slug) plus numeric id for legacy URLs.
     */
    protected function resolveShop($shop): ?Shop
    {
        $key = is_scalar($shop) ? trim((string) $shop) : '';
        if ($key === '') {
            return null;
        }

        // Prefer slug (what POST /chat uses), then id.
        $bySlug = Shop::query()->where('slug', $key)->first();
        if ($bySlug) {
            return $bySlug;
        }

        if (ctype_digit($key)) {
            return Shop::query()->where('id', (int) $key)->first();
        }

        return null;
    }

    /**
     * Widget-friendly conversation JSON (id, message, replies with user_id/attachments).
     *
     * @return array<string, mixed>
     */
    protected function conversationPayload(ChatConversation $conversation): array
    {
        $replies = [];
        foreach ($conversation->replies as $reply) {
            $attachments = [];
            if ($reply->relationLoaded('attachments')) {
                foreach ($reply->attachments as $att) {
                    $attachments[] = [
                        'id' => $att->id,
                        'path' => $att->path,
                        'name' => $att->name,
                        'extension' => $att->extension,
                        'url' => get_storage_file_url($att->path),
                    ];
                }
            }

            $replies[] = [
                'id' => $reply->id,
                'reply' => (string) ($reply->reply ?? ''),
                'user_id' => $reply->user_id,
                'customer_id' => $reply->customer_id,
                'created_at' => optional($reply->created_at)->toIso8601String(),
                'attachments' => $attachments,
                'type' => method_exists($reply, 'resolvedType') ? $reply->resolvedType() : ($reply->type ?? 'text'),
                'payload' => method_exists($reply, 'resolvedPayload') ? $reply->resolvedPayload() : ($reply->payload ?? null),
                'parent_id' => $reply->parent_id ?? null,
                'quoted_reply' => $reply->quoted_reply ?? null,
            ];
        }

        return [
            'id' => $conversation->id,
            'shop_id' => (int) $conversation->shop_id,
            'customer_id' => (int) $conversation->customer_id,
            'order_id' => $conversation->order_id ? (int) $conversation->order_id : null,
            'message' => (string) ($conversation->message ?? ''),
            'created_at' => optional($conversation->created_at)->toIso8601String(),
            'updated_at' => optional($conversation->updated_at)->toIso8601String(),
            'replies' => $replies,
        ];
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
        ])->latest('updated_at')->latest('id')->first();

        $isNewConversation = ! $conversation;

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
            // First message ever between this customer and shop — create the thread.
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
            return response()->json([
                'status' => 'error',
                'code' => 'login_required',
                'message' => trans('theme.login_to_chat'),
            ], 401);
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
            'shop_id' => $shop->id,
            'time' => $clock,
            'created_at' => optional($msg_object->created_at)->toIso8601String(),
            'attachments' => $attachmentsPayload,
            'type' => $resolvedType['type'],
            'payload' => $resolvedType['payload'],
            'is_new' => $isNewConversation,
        ], livechat_quote_socket_payload($quotedParent));

        // Realtime must never fail the HTTP save (first message especially).
        try {
            ChatSocketPublisher::publish($room, 'chat.message', $socketPayload);
            ChatSocketPublisher::publish(get_vendor_chat_room_id($shop), 'chat.message', $socketPayload);
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            event(new NewMessageEvent($msg_object, $replyText));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'status' => $isNewConversation ? 'created' : 'ok',
            'is_new' => $isNewConversation,
            'conversation_id' => $conversation->id,
            'reply_id' => $msg_object->id,
            'time' => $clock,
            'created_at' => optional($msg_object->created_at)->toIso8601String(),
            'attachments' => $attachmentsPayload,
            'parent_id' => $quotedParent?->id,
            'quoted_reply' => Reply::quoteSnapshot($quotedParent),
            'type' => $resolvedType['type'],
            'payload' => $resolvedType['payload'],
            'message' => $replyText,
        ], 200)->header('Cache-Control', 'no-store, private');
    }
}
