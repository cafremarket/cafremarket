<?php

namespace App\Services;

use App\Events\Chat\NewMessageEvent;
use App\Models\Order;
use App\Models\Reply;
use App\Services\ChatSocketPublisher;
use Illuminate\Support\Facades\Schema;
use Incevio\Package\LiveChat\Models\ChatConversation;

/**
 * Sends customer/vendor messages into the unified LiveChat thread
 * (one conversation per shop + customer). Order context is shared via
 * [order_share]{...} payloads — same pattern as [product_share].
 */
class OrderChatSyncService
{
    public const ORDER_SHARE_PREFIX = '[order_share]';

    public static function buildOrderSharePayload(Order $order, bool $isCustom = false): array
    {
        $order->loadMissing(['shop', 'inventories.image']);

        $firstItem = $order->inventories->first();
        $image = '';
        try {
            $image = $firstItem
                ? get_storage_file_url(optional($firstItem->image)->path, 'tiny_thumb')
                : '';
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $path = route('order.detail', $order, false);
        } catch (\Throwable $e) {
            $path = '/order/'.$order->id;
        }

        // Build the absolute URL from the CURRENT REQUEST's own Host header
        // rather than config('app.url'). Some environments override APP_URL
        // at the webserver/OS level in a way this app's own .env/config()
        // never sees, so a link built from config('app.url') can silently
        // point at the wrong domain (e.g. a leftover "localhost") even
        // though the browser is really on a different one. The request's
        // own host always matches whatever domain is actually being used.
        $url = app()->runningInConsole()
            ? url($path)
            : request()->getSchemeAndHttpHost().$path;

        $status = '';
        try {
            $status = method_exists($order, 'orderStatus')
                ? strip_tags((string) $order->orderStatus(true))
                : (string) ($order->order_status_id ?? '');
        } catch (\Throwable $e) {
            $status = (string) ($order->order_status_id ?? '');
        }

        $total = '';
        try {
            $total = get_formated_currency($order->grand_total, 2, $order->currency_id);
        } catch (\Throwable $e) {
            $total = (string) $order->grand_total;
        }

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'title' => 'Order #'.$order->order_number,
            'status' => $status,
            'total' => $total,
            'url' => $url,
            'image' => $image,
            'items_count' => $order->inventories->count(),
            'is_custom' => $isCustom,
        ];
    }

    public static function buildOrderShareMessage(Order $order, bool $isCustom = false): string
    {
        return self::ORDER_SHARE_PREFIX.json_encode(
            self::buildOrderSharePayload($order, $isCustom),
            JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Single LiveChat thread for a shop + customer (order context via [order_share] only).
     */
    public static function findShopChat(Order $order): ?ChatConversation
    {
        if (! class_exists(ChatConversation::class) || ! Schema::hasTable('chat_conversations')) {
            return null;
        }

        $customerId = $order->customer_id;
        $shopId = $order->shop_id;
        if (! $customerId || ! $shopId) {
            return null;
        }

        return ChatConversation::query()
            ->where('shop_id', $shopId)
            ->where('customer_id', $customerId)
            ->when(
                Schema::hasColumn('chat_conversations', 'order_id'),
                fn ($q) => $q->whereNull('order_id')
            )
            ->with(array_merge(
                function_exists('livechat_replies_eager_load')
                    ? livechat_replies_eager_load()
                    : ['replies.attachments'],
                ['shop', 'customer']
            ))
            ->first();
    }

    /**
     * Open (or create) the unified shop↔customer LiveChat for an order page.
     */
    public static function findOrCreateShopChat(Order $order): ?ChatConversation
    {
        $chat = self::findShopChat($order);
        if ($chat) {
            return $chat;
        }

        if (! class_exists(ChatConversation::class) || ! Schema::hasTable('chat_conversations')) {
            return null;
        }

        $customerId = $order->customer_id;
        $shopId = $order->shop_id;
        if (! $customerId || ! $shopId) {
            return null;
        }

        $attrs = [
            'shop_id' => $shopId,
            'customer_id' => $customerId,
            'message' => '',
            'status' => ChatConversation::STATUS_NEW,
        ];
        if (Schema::hasColumn('chat_conversations', 'order_id')) {
            $attrs['order_id'] = null;
        }

        return ChatConversation::create($attrs)
            ->load(array_merge(
                function_exists('livechat_replies_eager_load')
                    ? livechat_replies_eager_load()
                    : ['replies.attachments'],
                ['shop', 'customer']
            ));
    }

    /**
     * Post into the single shop↔customer LiveChat thread.
     *
     * @param  mixed  $attachmentFile  UploadedFile|array|null
     */
    public static function sendToShopChat(
        Order $order,
        string $replyText,
        string $senderType = 'customer',
        bool $shareOrder = false,
        $userId = null,
        $attachmentFile = null,
        ?int $parentId = null,
        bool $isCustomOrder = false
    ): ?ChatConversation {
        if (! class_exists(ChatConversation::class) || ! Schema::hasTable('chat_conversations')) {
            return null;
        }

        $customerId = $order->customer_id;
        $shopId = $order->shop_id;
        if (! $customerId || ! $shopId) {
            return null;
        }

        $chat = self::findShopChat($order);

        if ($shareOrder) {
            $sharePayload = self::buildOrderSharePayload($order, $isCustomOrder);
            $shareMsg = self::ORDER_SHARE_PREFIX.json_encode($sharePayload, JSON_UNESCAPED_UNICODE);
            $chat = self::appendReply(
                $chat, $shopId, $customerId, $shareMsg, $senderType, $userId, $order,
                null, Reply::TYPE_ORDER_SHARE, $sharePayload
            );
        }

        $text = trim($replyText);
        $hasAttachment = $attachmentFile !== null && $attachmentFile !== [];
        if ($text === '' && $hasAttachment) {
            $text = function_exists('livechat_message_for_attachment_only')
                ? livechat_message_for_attachment_only()
                : ' ';
        }

        if ($text !== '' && $text !== ' ') {
            $chat = self::appendReply($chat, $shopId, $customerId, $text, $senderType, $userId, $order, $parentId);
            if ($hasAttachment && $chat) {
                $lastReply = $chat->replies()->latest('id')->first();
                if ($lastReply) {
                    try {
                        $lastReply->saveAttachments($attachmentFile);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        } elseif ($text === ' ' && $hasAttachment) {
            $chat = self::appendReply($chat, $shopId, $customerId, $text, $senderType, $userId, $order, $parentId);
            if ($chat) {
                $lastReply = $chat->replies()->latest('id')->first();
                if ($lastReply) {
                    try {
                        $lastReply->saveAttachments($attachmentFile);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        }

        return $chat?->fresh(array_merge(
            function_exists('livechat_replies_eager_load')
                ? livechat_replies_eager_load()
                : ['replies.attachments'],
            ['shop', 'customer']
        ));
    }

    protected static function appendReply(
        ?ChatConversation $chat,
        int $shopId,
        int $customerId,
        string $replyText,
        string $senderType,
        $userId,
        Order $order,
        ?int $parentId = null,
        ?string $type = null,
        ?array $payload = null
    ): ChatConversation {
        if (! $chat) {
            $attrs = [
                'shop_id' => $shopId,
                'customer_id' => $customerId,
                'message' => $replyText,
                'status' => ChatConversation::STATUS_NEW,
            ];
            if (Schema::hasColumn('chat_conversations', 'order_id')) {
                $attrs['order_id'] = null;
            }
            $chat = ChatConversation::create($attrs);
        } else {
            $chat->bumpLastMessage($replyText, $senderType === 'customer');
        }

        $replyAttrs = [
            'reply' => $replyText,
            'read' => false,
            'type' => $type ?? (
                $replyText === (function_exists('livechat_message_for_attachment_only')
                    ? livechat_message_for_attachment_only()
                    : '[attachment]')
                    ? Reply::TYPE_ATTACHMENT
                    : Reply::TYPE_TEXT
            ),
            'payload' => $payload,
        ];
        if ($senderType === 'customer') {
            $replyAttrs['customer_id'] = $customerId;
            $replyAttrs['user_id'] = null;
        } else {
            $replyAttrs['customer_id'] = null;
            $replyAttrs['user_id'] = $userId;
        }

        $quotedParent = null;
        if ($parentId && Schema::hasColumn('replies', 'parent_id')) {
            $quotedParent = Reply::resolveQuotedParent($chat, ['parent_id' => $parentId]);
            if ($quotedParent) {
                $replyAttrs['parent_id'] = $quotedParent->id;
            }
        }

        $chatReply = $chat->replies()->create($replyAttrs);
        self::publishRealtime($chat, $chatReply, $replyText, $senderType, $quotedParent);

        try {
            event(new NewMessageEvent($chatReply, $replyText));
        } catch (\Throwable $e) {
            report($e);
        }

        return $chat;
    }

    public static function publishRealtime(
        ChatConversation $chat,
        $chatReply,
        string $replyText,
        string $senderType,
        ?Reply $quotedParent = null
    ): void {
        $chat->loadMissing('shop');

        $attachmentsPayload = function_exists('livechat_socket_attachments_payload')
            ? livechat_socket_attachments_payload($chatReply)
            : [];

        $clock = function_exists('livechat_format_message_time')
            ? livechat_format_message_time($chatReply->created_at)
            : optional($chatReply->created_at)->format('H:i');

        $resolvedType = $chatReply->resolvedType();
        $payload = array_merge([
            'text' => $replyText,
            'sender_type' => $senderType,
            'conversation_id' => $chat->id,
            'reply_id' => $chatReply->id,
            'customer_id' => $chat->customer_id,
            'shop_id' => $chat->shop_id,
            'chat_type' => $resolvedType === Reply::TYPE_ORDER_SHARE ? 'order_share' : 'product',
            'time' => $clock,
            'created_at' => optional($chatReply->created_at)->toIso8601String(),
            'attachments' => $attachmentsPayload,
            'type' => $resolvedType,
            'payload' => $chatReply->resolvedPayload(),
        ], function_exists('livechat_quote_socket_payload')
            ? livechat_quote_socket_payload($quotedParent)
            : []);

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
    }

    /** @deprecated Kept for callers during transition — maps to sendToShopChat */
    public static function afterCustomerOrVendorOrderMessage(
        Order $order,
        $msg,
        bool $isNewConversation,
        string $senderType = 'customer'
    ): void {
        $text = $msg instanceof \App\Models\Reply
            ? (string) ($msg->reply ?? '')
            : (string) ($msg->message ?? '');

        self::sendToShopChat(
            $order,
            $text,
            $senderType,
            true,
            $msg->user_id ?? null
        );
    }

    /** @deprecated No longer mirrors into separate Message threads */
    public static function mirrorMerchantReplyToOrder(ChatConversation $chat, $chatReply, string $replyText): void
    {
        // Intentionally no-op: one chat system only.
    }

    public static function mirrorToLiveChat(Order $order, $msg, string $senderType = 'customer'): ?ChatConversation
    {
        $text = $msg instanceof \App\Models\Reply
            ? (string) ($msg->reply ?? '')
            : (string) ($msg->message ?? '');

        return self::sendToShopChat($order, $text, $senderType, false, $msg->user_id ?? null);
    }
}
