<?php

namespace Incevio\Package\LiveChat\Http\Controllers;

use App\Events\Chat\NewMessageEvent;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Reply;
use App\Models\Shop;
use App\Services\ChatCustomOrderService;
use App\Services\ChatSocketPublisher;
use App\Services\Shipping\ShippingCalculator;
use App\Services\Tax\ProductTaxCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Incevio\Package\LiveChat\Http\Requests\SaveChatConversationRequest;
use Incevio\Package\LiveChat\Http\Requests\ViewChatConversationRequest;
use Incevio\Package\LiveChat\Models\ChatConversation;

class AdminChatController extends Controller
{
    /**
     * Resolve the `type`/`payload` to persist for an incoming message,
     * preferring explicit request fields and falling back to inferring
     * an attachment-only message the same way the body placeholder does.
     * Mirrors Api\ConversationController::resolveIncomingType().
     *
     * @return array{type: string, payload: array<string, mixed>|null}
     */
    protected function resolveIncomingType(Request $request, string $replyText): array
    {
        $type = $request->input('type');
        $payload = $request->input('payload');
        $payload = is_array($payload) ? $payload : (is_string($payload) ? json_decode($payload, true) : null);
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
     * List shop chat conversations (unified inbox — product + order shares).
     */
    public function index(Request $request)
    {
        Gate::authorize('index', ChatConversation::class);

        $chats = Schema::hasTable('chat_conversations')
            ? ChatConversation::mine()
                ->with('customer')
                ->latest('updated_at')
                ->get()
            : collect();

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

        $chat->loadMissing(livechat_replies_eager_load());

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
            return response()->json(['message' => trans('messages.chat_reply_not_allowed')], 403);
        }

        $shopId = Auth::user()?->merchantId();
        if ($shopId && (int) $chat->shop_id !== (int) $shopId) {
            return response()->json(['message' => trans('messages.conversation_not_found')], 404);
        }

        $replyText = trim((string) $request->input('message', ''));
        if ($replyText === '' && ($request->hasFile('photo') || $request->filled('photo'))) {
            $replyText = livechat_message_for_attachment_only();
        }

        if ($replyText === '' && ! $request->hasFile('photo') && ! $request->filled('photo')) {
            return response()->json(['message' => trans('messages.empty_message')], 422);
        }

        $quotedParent = Reply::resolveQuotedParent($chat, $request);
        $incoming = $this->resolveIncomingType($request, $replyText);

        $userId = $request->input('user_id') ?: Auth::id();

        $createAttrs = [
            'customer_id' => null,
            'user_id' => $userId,
            'reply' => $replyText,
            'read' => false,
            'type' => $incoming['type'],
            'payload' => $incoming['payload'],
        ];
        if ($quotedParent) {
            $createAttrs['parent_id'] = $quotedParent->id;
        }
        $reply = $chat->replies()->create($createAttrs);

        $chat->bumpLastMessage($replyText, false);

        if ($request->hasFile('photo')) {
            $reply->saveAttachments($request->file('photo'));
        } elseif ($request->filled('photo')) {
            $reply->saveAttachments(create_file_from_base64($request->get('photo')));
        }

        $attachmentsPayload = livechat_socket_attachments_payload($reply);

        $clock = livechat_format_message_time($reply->created_at);
        $createdAt = optional($reply->created_at)->toIso8601String();

        $payload = array_merge([
            'text' => $replyText,
            'sender_type' => 'merchant',
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
            'parent_id' => $quotedParent?->id,
            'quoted_reply' => Reply::quoteSnapshot($quotedParent),
            'type' => $reply->resolvedType(),
            'payload' => $reply->resolvedPayload(),
            'ok' => true,
        ], 200);
    }

    /**
     * Build a custom-priced order (arbitrary line items, shipping, tax,
     * discount) from the merchant web panel and share it in this thread.
     * Web-session counterpart of Api\Vendor\OrderConversationController::storeCustomOrder.
     */
    public function createCustomOrder(Request $request, ChatConversation $chat)
    {
        $vendor = Auth::user();
        $shopId = $vendor?->merchantId();

        if (! $shopId || (int) $chat->shop_id !== (int) $shopId) {
            return response()->json(['message' => trans('messages.conversation_not_found')], 404);
        }

        if (! $chat->customer_id) {
            return response()->json(['message' => trans('messages.conversation_no_customer')], 422);
        }

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.inventory_id' => 'nullable|integer',
            'items.*.title' => 'required_without:items.*.inventory_id|nullable|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_method_id' => 'nullable|integer',
            'billing_address' => 'nullable|string',
            'shipping_address_id' => 'nullable|integer',
            'billing_address_id' => 'nullable|integer',
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(ChatCustomOrderService::class)->createOrderFromChat(
                $chat,
                $request,
                $vendor,
                $data['items'],
                [
                    'shipping_cost' => $data['shipping_cost'] ?? 0,
                    'tax' => $data['tax'] ?? 0,
                    'discount' => $data['discount'] ?? 0,
                    'payment_method_id' => $data['payment_method_id'] ?? null,
                    'billing_address' => $data['billing_address'] ?? null,
                    'shipping_address_id' => $data['shipping_address_id'] ?? null,
                    'billing_address_id' => $data['billing_address_id'] ?? null,
                    'note' => trim((string) ($data['note'] ?? '')),
                ]
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage() ?: 'Something went wrong'], 400);
        }

        if (livechat_is_merchant_panel()) {
            return view('liveChat::merchant._conversation', [
                'chat' => $chat->fresh()->load(livechat_replies_eager_load()),
            ]);
        }

        return view('liveChat::_chat_conversation', [
            'chat' => $chat->fresh()->load(livechat_replies_eager_load()),
        ]);
    }

    /**
     * This merchant's own catalog listings, for the "Create Order" line-item
     * product picker (real inventory, not a freeform-typed name/price).
     */
    public function searchInventory(Request $request)
    {
        $shopId = Auth::user()?->merchantId();
        if (! $shopId) {
            return response()->json(['data' => []]);
        }

        $term = trim((string) $request->get('q', ''));

        $inventories = \App\Models\Inventory::query()
            ->where('shop_id', $shopId)
            ->where('is_chat_custom', false)
            ->when($term !== '', fn ($q) => $q->where('title', 'like', '%'.$term.'%'))
            ->with(['image', 'attributeValues'])
            ->latest('id')
            ->take(20)
            ->get();

        return response()->json([
            'data' => $inventories->map(fn ($inventory) => [
                'inventory_id' => $inventory->id,
                'slug' => $inventory->slug,
                'title' => self::titleWithVariant($inventory),
                'price' => get_formated_currency($inventory->current_sale_price()),
                'raw_price' => (float) $inventory->current_sale_price(),
                'stock' => (int) $inventory->stock_quantity,
                'image' => get_storage_file_url(optional($inventory->image)->path, 'tiny_thumb'),
                'url' => storefront_product_url($inventory),
            ]),
        ]);
    }

    /**
     * "Product Name (Color - Size)" — a bare inventory title is often
     * identical across a product's variants, which makes a multi-variant
     * catalog impossible to tell apart in a search picker.
     */
    public static function titleWithVariant(\App\Models\Inventory $inventory): string
    {
        $variant = $inventory->relationLoaded('attributeValues')
            ? $inventory->attributeValues->pluck('value')->filter()->implode(' - ')
            : '';

        return $variant !== '' ? "{$inventory->title} ({$variant})" : $inventory->title;
    }

    /**
     * This shop's orders with the specific customer of this chat thread, for
     * the "Share Order" picker — never any other shop's or customer's orders.
     */
    public function searchOrders(Request $request, ChatConversation $chat)
    {
        $shopId = Auth::user()?->merchantId();
        if (! $shopId || (int) $chat->shop_id !== (int) $shopId || ! $chat->customer_id) {
            return response()->json(['data' => []]);
        }

        $term = trim((string) $request->get('q', ''));

        $orders = \App\Models\Order::query()
            ->where('shop_id', $shopId)
            ->where('customer_id', $chat->customer_id)
            ->when($term !== '', fn ($q) => $q->where('order_number', 'like', '%'.$term.'%'))
            ->latest('id')
            ->take(20)
            ->get();

        return response()->json([
            'data' => $orders->map(fn ($order) => \App\Services\OrderChatSyncService::buildOrderSharePayload($order)),
        ]);
    }

    /**
     * Real per-product tax/shipping for the current Create Order line items —
     * the same ProductTaxCalculator/ShippingCalculator the storefront
     * checkout uses, not a blind shop-wide default. Custom (non-catalog)
     * lines have no product to look up, so they contribute nothing here.
     */
    public function calculateOrderTotals(Request $request)
    {
        $shopId = Auth::user()?->merchantId();
        $shop = $shopId ? Shop::with('config')->find($shopId) : null;

        $empty = ['tax_amount' => 0, 'tax_breakdown' => [], 'shipping_amount' => 0, 'shipping_breakdown' => []];
        if (! $shop) {
            return response()->json($empty);
        }

        $data = $request->validate([
            'items' => 'required|array',
            'items.*.inventory_id' => 'nullable|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $taxCalculator = app(ProductTaxCalculator::class);
        $shippingCalculator = app(ShippingCalculator::class);

        $taxAmount = 0.0;
        $taxBreakdown = [];
        $shippingAmount = 0.0;
        $shippingBreakdown = [];

        foreach ($data['items'] as $row) {
            $inventoryId = $row['inventory_id'] ?? null;
            if (! $inventoryId) {
                continue; // custom item — no catalog product to look up rules for
            }

            $inventory = Inventory::where('id', $inventoryId)
                ->where('shop_id', $shop->id)
                ->with('product.taxes')
                ->first();
            if (! $inventory) {
                continue;
            }

            $qty = (int) $row['quantity'];
            $price = (float) $row['unit_price'];

            $taxResult = $taxCalculator->calculateForItem($inventory, $qty, $price);
            if ($taxResult['amount'] > 0) {
                $taxAmount += $taxResult['amount'];
                foreach ($taxResult['taxes'] as $line) {
                    $taxBreakdown[] = [
                        'title' => $line['title'],
                        'tax_name' => $line['tax_name'],
                        'amount' => get_formated_currency($line['amount']),
                    ];
                }
            }

            $shipCharge = $shippingCalculator->calculateForItem($inventory, $shop->config, null);
            if ($shipCharge > 0) {
                $shippingAmount += $shipCharge;
                $shippingBreakdown[] = [
                    'title' => $inventory->title,
                    'amount' => get_formated_currency($shipCharge),
                ];
            }
        }

        return response()->json([
            'tax_amount' => round($taxAmount, 2),
            'tax_breakdown' => $taxBreakdown,
            'shipping_amount' => round($shippingAmount, 2),
            'shipping_breakdown' => $shippingBreakdown,
        ]);
    }
}
