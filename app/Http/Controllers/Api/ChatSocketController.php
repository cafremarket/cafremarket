<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Room names and subscription tokens for chat-ws-node. Apps must use the room returned here
 * (not build it themselves) and send the token with `subscribe`.
 */
class ChatSocketController extends Controller
{
    /**
     * Customer: their own thread with a shop.
     */
    public function customerRoom(Request $request): JsonResponse
    {
        $request->validate(['shop_id' => 'required|integer|min:1']);

        $room = chat_thread_room($request->integer('shop_id'), Auth::guard('api')->id());

        return response()->json(['room' => $room, 'token' => chat_room_token($room)]);
    }

    /**
     * Vendor: a thread of their shop with one customer, or the shop inbox when no customer is given.
     */
    public function vendorRoom(Request $request): JsonResponse
    {
        $request->validate(['customer_id' => 'nullable|integer|min:1']);

        $shopId = (int) Auth::guard('vendor_api')->user()->merchantId();
        abort_unless($shopId > 0, 403);

        $room = $request->filled('customer_id')
            ? chat_thread_room($shopId, $request->integer('customer_id'))
            : get_vendor_chat_room_id($shopId);

        return response()->json(['room' => $room, 'token' => chat_room_token($room)]);
    }
}
