<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\FulfillOrderRequest;
use App\Http\Requests\Validations\OrderDetailRequest;
use App\Models\DeliveryBoy;
use App\Models\Order;
use App\Services\Delivery\DeliveryDispatchService;
use App\Services\FCMService;
use Illuminate\Http\Request;

class OrderFulfillmentController extends Controller
{
    /**
     * Buyer confirmed goods received
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function fulfill(FulfillOrderRequest $request, Order $order)
    {
        // Check permission

        try {
            $order->fulfill($request);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.order_updated_successfully')], 200);
    }

    /**
     * Buyer confirmed goods received
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function delivered(OrderDetailRequest $request, Order $order)
    {
        try {
            $order->mark_as_goods_received();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.order_updated_successfully')], 200);
    }

    /**
     * Return list of the shop's own delivery boys
     *
     * @return \Illuminate\Http\Response
     */
    public function delivery_boys(Order $order, DeliveryDispatchService $dispatchService)
    {
        return [
            'shop_riders' => $dispatchService->getAvailableShopRiders($order->shop_id)->pluck('nice_name', 'id'),
        ];
    }

    /**
     * Assign a shop-owned delivery boy
     *
     * @return \Illuminate\Http\Response
     */
    public function assign_delivery_boy(Request $request, Order $order, DeliveryDispatchService $dispatchService)
    {
        try {
            $rider = DeliveryBoy::findOrFail($request->input('delivery_boy_id'));
            $dispatchService->assignShopRider($order, $rider);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.order_updated_successfully')], 200);
    }

    /**
     * Add courier details for this order (self-ship / third-party courier).
     *
     * @return \Illuminate\Http\Response
     */
    public function assign_courier(Request $request, Order $order)
    {
        if (! $request->filled('courier_name') || ! $request->filled('courier_phone')) {
            return response()->json(['message' => trans('app.courier_details_required')], 422);
        }

        $order->fulfillment_method = Order::FULFILLMENT_METHOD_COURIER;
        $order->courier_name = $request->input('courier_name');
        $order->courier_phone = $request->input('courier_phone');
        $order->courier_tracking_number = $request->input('courier_tracking_number');
        $order->courier_added_at = now();
        $order->otp = Order::generateDeliveryOtp();

        if ($order->order_status_id < Order::STATUS_AWAITING_DELIVERY) {
            $order->order_status_id = Order::STATUS_AWAITING_DELIVERY;
        }

        $order->save();

        $customer_token = optional($order->customer)->fcm_token;

        if (! is_null($customer_token)) {
            FCMService::send($customer_token, [
                'title' => trans('notifications.otp_send.subject'),
                'body' => trans('notifications.otp_send.message', ['message' => $order->otp]),
            ]);
        }

        return response()->json(['message' => trans('api.order_updated_successfully')], 200);
    }
}
