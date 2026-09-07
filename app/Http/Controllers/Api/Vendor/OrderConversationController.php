<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\OrderDetailRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Order;
use App\Services\OrderChatSyncService;
use Illuminate\Support\Facades\Auth;

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

        return new ConversationResource($chat->fresh(['replies.attachments', 'shop', 'customer']));
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
            $attachment
        );

        if (! $chat) {
            return response()->json(['message' => trans('api.something_went_wrong')], 500);
        }

        return new ConversationResource($chat);
    }
}
