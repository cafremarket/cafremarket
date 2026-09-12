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
use Illuminate\Support\Facades\DB;

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

        if ($order->isDelivered()) {
            return response()->json(['message' => trans('app.order_already_delivered')], 422);
        }

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
        if ($order->isDelivered()) {
            return response()->json(['message' => trans('app.order_already_delivered')], 422);
        }

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
        if ($order->isDelivered()) {
            return response()->json(['message' => trans('app.order_already_delivered')], 422);
        }

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

        try {
            $order = DB::transaction(function () use ($order, $request) {
                // Locked re-read guards against a concurrent delivery
                // confirmation landing between this request's initial check
                // and its save — see DeliveryDispatchService::assignShopRider.
                $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

                if ($locked->isDelivered()) {
                    throw new \RuntimeException(trans('app.order_already_delivered'));
                }

                $locked->fulfillment_method = Order::FULFILLMENT_METHOD_COURIER;
                $locked->delivery_boy_id = null;
                $locked->courier_name = $request->input('courier_name');
                $locked->courier_phone = $request->input('courier_phone');
                $locked->courier_tracking_number = $request->input('courier_tracking_number');
                $locked->courier_added_at = now();

                if (empty($locked->otp)) {
                    $locked->otp = Order::generateDeliveryOtp();
                }

                // Courier assignment moves the order out of "unfulfilled" into awaiting delivery.
                if ($locked->order_status_id < Order::STATUS_AWAITING_DELIVERY) {
                    $locked->order_status_id = Order::STATUS_AWAITING_DELIVERY;
                }

                $locked->save();

                return $locked;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $customer_token = optional($order->customer)->fcm_token;

        if (! is_null($customer_token)) {
            FCMService::send($customer_token, [
                'title' => trans('notifications.otp_send.subject'),
                'body' => trans('notifications.otp_send.message', ['message' => $order->otp]),
            ]);
        }

        return response()->json(['message' => trans('api.order_updated_successfully')], 200);
    }

    /**
     * Vendor-side alternative to the customer's "Confirm Received" tap: the
     * vendor reads the OTP back from the customer over a call and enters it
     * here. Verified server-side against the same OTP the customer's app
     * shows, so this can't be used to bypass confirmation without the code.
     *
     * @return \Illuminate\Http\Response
     */
    public function confirm_courier_otp(Request $request, Order $order)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        try {
            DB::transaction(function () use ($order, $request) {
                $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

                if ($locked->isDelivered()) {
                    throw new \RuntimeException(trans('app.order_already_delivered'));
                }

                if (! $locked->hasCourier()) {
                    throw new \RuntimeException(trans('app.courier_details_required'));
                }

                if (empty($locked->otp) || ! hash_equals((string) $locked->otp, (string) $request->input('otp'))) {
                    throw new \RuntimeException(trans('app.invalid_otp'));
                }

                $locked->mark_as_goods_received();
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.order_updated_successfully')], 200);
    }
}
