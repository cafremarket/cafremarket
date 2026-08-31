<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\FulfillOrderRequest;
use App\Http\Requests\Validations\OrderDetailRequest;
use App\Models\DeliveryBoy;
use App\Models\Order;
use App\Services\Delivery\DeliveryDispatchService;
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
     * Return list of delivery boys
     *
     * @return \Illuminate\Http\Response
     */
    public function delivery_boys(Order $order, DeliveryDispatchService $dispatchService)
    {
        $shopRiders = $dispatchService->getAvailableShopRiders($order->shop_id)
            ->pluck('nice_name', 'id');

        return [
            'shop_riders' => $shopRiders,
            'platform_riders' => $dispatchService->findNearbyPlatformRiders($order->shop)->map(function ($rider) {
                return [
                    'id' => $rider->id,
                    'name' => $rider->nice_name ?: $rider->getName(),
                    'distance_km' => round($rider->distance_km, 2),
                ];
            })->values(),
        ];
    }

    /**
     * Assign a delivery boy
     *
     * @return \Illuminate\Http\Response
     */
    public function assign_delivery_boy(Request $request, Order $order, DeliveryDispatchService $dispatchService)
    {
        try {
            if ($request->filled('platform_rider_id') || $request->boolean('use_platform')) {
                $dispatchService->requestPlatformDelivery($order, $request->input('platform_rider_id'));
            } else {
                $rider = DeliveryBoy::findOrFail($request->input('delivery_boy_id'));
                if ($rider->isPlatform()) {
                    $dispatchService->assignPlatformRider($order, $rider);
                } else {
                    $dispatchService->assignShopRider($order, $rider);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.order_updated_successfully')], 200);
    }
}
