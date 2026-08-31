<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryBoy;
use App\Models\Order;
use App\Services\Delivery\DeliveryDispatchService;
use Illuminate\Http\Request;

class HyperlocalDispatchController extends Controller
{
    public function index(DeliveryDispatchService $dispatchService)
    {
        $platformRiders = DeliveryBoy::platformRiders()
            ->online()
            ->where('status', true)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->get();

        $unassignedOrders = Order::query()
            ->whereNull('delivery_boy_id')
            ->where('fulfilment_type', Order::FULFILMENT_TYPE_DELIVER)
            ->whereIn('order_status_id', [Order::STATUS_CONFIRMED, Order::STATUS_FULFILLED])
            ->with(['shop:id,name,slug', 'customer:id,name,email'])
            ->latest()
            ->limit(50)
            ->get();

        return view('admin.hyperlocal.dispatch', compact('platformRiders', 'unassignedOrders', 'dispatchService'));
    }

    public function data(DeliveryDispatchService $dispatchService)
    {
        $riders = DeliveryBoy::platformRiders()
            ->online()
            ->where('status', true)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->get(['id', 'nice_name', 'first_name', 'last_name', 'current_latitude', 'current_longitude', 'last_location_at']);

        $orders = Order::query()
            ->whereNull('delivery_boy_id')
            ->where('fulfilment_type', Order::FULFILMENT_TYPE_DELIVER)
            ->whereIn('order_status_id', [Order::STATUS_CONFIRMED, Order::STATUS_FULFILLED])
            ->with(['shop.addresses'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($order) {
                $address = $order->shop?->storeAddress();

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'shop_name' => $order->shop?->name,
                    'latitude' => $address?->latitude,
                    'longitude' => $address?->longitude,
                ];
            })
            ->filter(fn ($order) => $order['latitude'] && $order['longitude'])
            ->values();

        return response()->json([
            'riders' => $riders,
            'orders' => $orders,
            'max_dispatch_radius_km' => $dispatchService->maxDispatchRadius(),
        ]);
    }
}
