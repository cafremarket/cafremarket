<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\OrderDetailRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Shop;
use App\Services\ChatCustomOrderService;
use App\Services\OrderChatSyncService;
use App\Services\Shipping\ShippingCalculator;
use App\Services\Tax\ProductTaxCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Incevio\Package\LiveChat\Http\Controllers\AdminChatController;
use Incevio\Package\LiveChat\Models\ChatConversation;

/**
 * Order “conversation” endpoints are aliases of the unified LiveChat
 * (one ChatConversation per shop + customer). Order context is shared
 * via [order_share] — no separate Message/order thread.
 */
class OrderConversationController extends Controller
{
    /**
     * Load the shop↔customer LiveChat for this order.
     */
    public function index(OrderDetailRequest $request, Order $order)
    {
        $chat = OrderChatSyncService::findShopChat($order);

        if (! $chat) {
            return response()->json(['message' => trans('api.contact_customer')], 200);
        }

        $chat->markPeerRepliesAsRead('merchant');

        return new ConversationResource($chat->fresh(array_merge(livechat_replies_eager_load(), ['shop', 'customer'])));
    }

    /**
     * Reply in the same LiveChat used for product/seller chat.
     */
    public function respond(OrderDetailRequest $request, Order $order)
    {
        $userId = Auth::guard('vendor_api')->user()->id;

        $replyText = trim((string) ($request->input('message') ?? $request->query('message') ?? ''));
        $shareOrder = $request->boolean('share_order', true);

        $attachment = null;
        if ($request->has('attachments')) {
            $attachment = create_file_from_base64($request->get('attachments'));
        } elseif ($request->hasFile('photo')) {
            $attachment = $request->file('photo');
        } elseif ($request->filled('photo')) {
            $attachment = create_file_from_base64($request->get('photo'));
        }

        if ($replyText === '' && ! $attachment && ! $shareOrder) {
            return response()->json([
                'message' => trans('validation.required', ['attribute' => 'message']),
            ], 422);
        }

        $chat = OrderChatSyncService::sendToShopChat(
            $order->fresh(['shop', 'customer', 'inventories.image']),
            $replyText,
            'merchant',
            $shareOrder,
            $userId,
            $attachment,
            (int) $request->input('parent_id', 0) ?: null
        );

        if (! $chat) {
            return response()->json(['message' => trans('api.something_went_wrong')], 500);
        }

        return new ConversationResource($chat);
    }

    /**
     * Build a custom-priced order (arbitrary line items, shipping, tax,
     * discount) from inside this chat thread and share it as an order
     * card in the same conversation.
     */
    public function storeCustomOrder(Request $request, ChatConversation $chat)
    {
        $vendor = Auth::guard('vendor_api')->user();

        if ((int) $chat->shop_id !== (int) $vendor->merchantId()) {
            return response()->json(['message' => trans('responses.unauthorized')], 403);
        }

        if (! $chat->customer_id) {
            return response()->json(['message' => trans('api.something_went_wrong')], 422);
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
                    'note' => trim((string) ($data['note'] ?? '')),
                ]
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage() ?: trans('api.something_went_wrong')], 400);
        }

        return new ConversationResource($chat->fresh(array_merge(
            function_exists('livechat_replies_eager_load')
                ? livechat_replies_eager_load()
                : ['replies.attachments'],
            ['shop', 'customer']
        )));
    }

    /**
     * This vendor's own catalog listings, for the app's "Share Product" /
     * Create Order product picker (real inventory, not a freeform name/price).
     * Mirrors Incevio\Package\LiveChat\Http\Controllers\AdminChatController::searchInventory()
     * (web merchant panel) so both surfaces behave identically.
     */
    public function searchInventory(Request $request)
    {
        $shopId = Auth::guard('vendor_api')->user()?->merchantId();
        if (! $shopId) {
            return response()->json(['data' => []]);
        }

        $term = trim((string) $request->get('q', ''));

        $inventories = Inventory::query()
            ->where('shop_id', $shopId)
            ->where('is_chat_custom', false)
            ->when($term !== '', fn ($q) => $q->where('title', 'like', '%'.$term.'%'))
            ->with(['image', 'attributeValues'])
            ->latest('id')
            ->take(20)
            ->get();

        return response()->json([
            'data' => $inventories->map(fn (Inventory $inventory) => [
                'inventory_id' => $inventory->id,
                'slug' => $inventory->slug,
                'title' => AdminChatController::titleWithVariant($inventory),
                'price' => get_formated_currency($inventory->current_sale_price()),
                'raw_price' => (float) $inventory->current_sale_price(),
                'stock' => (int) $inventory->stock_quantity,
                'image' => get_storage_file_url(optional($inventory->image)->path, 'tiny_thumb'),
                'url' => storefront_product_url($inventory),
            ]),
        ]);
    }

    /**
     * This shop's orders with the specific customer of this chat thread, for
     * the app's "Share Order" picker — never any other shop's or customer's
     * orders. Mirrors AdminChatController::searchOrders() (web merchant panel).
     */
    public function searchOrders(Request $request, ChatConversation $chat)
    {
        $shopId = Auth::guard('vendor_api')->user()?->merchantId();
        if (! $shopId || (int) $chat->shop_id !== (int) $shopId || ! $chat->customer_id) {
            return response()->json(['data' => []]);
        }

        $term = trim((string) $request->get('q', ''));

        $orders = Order::query()
            ->where('shop_id', $shopId)
            ->where('customer_id', $chat->customer_id)
            ->when($term !== '', fn ($q) => $q->where('order_number', 'like', '%'.$term.'%'))
            ->latest('id')
            ->take(20)
            ->get();

        return response()->json([
            'data' => $orders->map(fn (Order $order) => OrderChatSyncService::buildOrderSharePayload($order)),
        ]);
    }

    /**
     * Real per-product tax/shipping for the app's Create Order line items —
     * the same ProductTaxCalculator/ShippingCalculator the storefront
     * checkout (and the web merchant panel's Create Order) use, not a blind
     * shop-wide default. Custom (non-catalog) lines contribute nothing here.
     * Mirrors AdminChatController::calculateOrderTotals().
     */
    public function calculateOrderTotals(Request $request)
    {
        $shopId = Auth::guard('vendor_api')->user()?->merchantId();
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
                continue;
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
