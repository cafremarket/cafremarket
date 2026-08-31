<?php

namespace App\Http\Controllers\Api\DeliveryBoy;

use App\Helpers\ApiAlert;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryBoy\MyDeliveryOtpRequest;
use App\Http\Requests\DeliveryBoy\MyDeliveryRequest;
use App\Http\Resources\FeedbackResource;
use App\Http\Resources\OrderLightResource;
use App\Http\Resources\OrderResource;
use App\Models\DeliveryBoy;
use App\Models\Order;
use App\Services\Delivery\DeliveryDispatchService;
use App\Services\FCMService;
use App\Services\Geo\DistanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use ApiAlert;

    /**
     * Delivery boy orders and You can pass filter to get unassign unfulfill orders
     *
     * @return [orders]
     */
    public function index(Request $request)
    {
        $rider = Auth::guard('delivery_boy-api')->user();

        if ($rider->isPlatform()) {
            return $this->platformOrders($request, $rider);
        }

        $orders = Order::where('shop_id', $rider->shop_id);

        switch ($request->get('filter')) {
            case 'all_orders':
                $orders = $orders->withArchived();
                break;

            case 'unassign':
                $orders = $orders->unAssigned();
                break;

            case 'unpaid':
                $orders = $orders->unpaid();
                break;

            case 'paid':
                $orders = $orders->paid();
                break;

            case 'assigned':
            default:
                // $orders = $orders->toDeliver()->myDelivery();
                $orders = $orders->myDelivery();
                break;
        }

        return OrderLightResource::collection($orders->get());
    }

    /**
     * This method will show the order details by id
     *
     * @param [order_id]
     * @return [order]
     */
    public function show(MyDeliveryRequest $request, Order $order)
    {
        return new OrderResource($order);
    }

    /**
     * This method will updated the order delivery status
     *
     * @param [order_id] [request]
     * @return [orders]
     */
    public function markAsDelivered(MyDeliveryRequest $request, Order $order)
    {
        try {
            $order->mark_as_goods_received();
            $order->otp = null;
            $order->save();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(trans('api.order_status_updated'));
    }

    /**
     * This method will updated the order payment status
     *
     * @param [order_id] [request]
     * @return [orders]
     */
    public function markAsPaid(MyDeliveryRequest $request, Order $order)
    {
        try {
            $order->markAsPaid();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(trans('api.order_status_updated'));
    }

    /**
     * This will assign a delivery boy to an order
     *
     * @param [order_id] [request]
     */
    public function assignActiveOrder($orderID, DeliveryDispatchService $dispatchService)
    {
        try {
            $order = Order::findOrFail($orderID);
            $rider = Auth::guard('delivery_boy-api')->user();

            if ($rider->isPlatform()) {
                $dispatchService->assignPlatformRider($order, $rider);
            } else {
                $dispatchService->assignShopRider($order, $rider);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('responses.order_assigned_successfully')], 200);
    }

    public function updateOrderStatus(MyDeliveryRequest $request, Order $order)
    {
        try {
            if ($request->filled('order_status_id')) {
                $order->order_status_id = $request->order_status_id;
            }

            if ($request->filled('status')) {
                $order->order_status_id = $request->status;
            }

            if ((int) $order->order_status_id === Order::STATUS_DELIVERED) {
                $order->mark_as_goods_received();
                $order->otp = null;
            }

            $order->save();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(trans('api.order_status_updated'));
    }

    protected function platformOrders(Request $request, DeliveryBoy $rider)
    {
        $orders = Order::query()
            ->where('fulfilment_type', Order::FULFILMENT_TYPE_DELIVER)
            ->whereIn('order_status_id', [Order::STATUS_CONFIRMED, Order::STATUS_FULFILLED, Order::STATUS_AWAITING_DELIVERY]);

        switch ($request->get('filter')) {
            case 'assigned':
                $orders = $orders->where('delivery_boy_id', $rider->id);
                break;
            case 'all_orders':
                $orders = $orders->withArchived();
                break;
            case 'unassign':
            default:
                $orders = $orders->whereNull('delivery_boy_id')
                    ->where('delivery_mode', DeliveryDispatchService::MODE_SYSTEM);
                break;
        }

        if ($rider->current_latitude && $rider->current_longitude) {
            $distance = app(DistanceService::class);
            $maxRadius = app(DeliveryDispatchService::class)->maxDispatchRadius();

            $orders = $orders->get()->filter(function ($order) use ($distance, $rider, $maxRadius) {
                $shopAddress = $order->shop?->storeAddress();
                if (! $shopAddress?->latitude || ! $shopAddress?->longitude) {
                    return false;
                }

                return $distance->distanceKm(
                    (float) $rider->current_latitude,
                    (float) $rider->current_longitude,
                    (float) $shopAddress->latitude,
                    (float) $shopAddress->longitude
                ) <= $maxRadius;
            });
        } else {
            $orders = $orders->get();
        }

        return OrderLightResource::collection($orders);
    }

    public function todayOrders()
    {
        $tOrders = Order::where('delivery_boy_id', Auth::guard('delivery_boy-api')->user()->id)
            ->whereDate('created_at', today())
            ->get();

        return OrderLightResource::collection($tOrders);
    }

    /**
     * Send otp to customer for this order
     */
    public function sendOtp(MyDeliveryOtpRequest $request, Order $order)
    {
        $order->otp = $request->otp;
        $order->save();

        $customer_token = optional($order->customer)->fcm_token;

        if (! is_null($customer_token)) {
            FCMService::send($customer_token, [
                'title' => trans('notifications.otp_send.subject'),
                'body' => trans('notifications.otp_send.message', ['message' => $order->otp]),
            ]);
        }

        return response()->json(['message' => trans('responses.otp_sent_successfully')], 200);
    }

    public function showDeliveryBoyFeedbacks(Request $request)
    {
        $deliveryBoy = Auth::guard('delivery_boy-api')->user();

        return FeedbackResource::collection($deliveryBoy->feedbacks()->paginate());
    }
}
