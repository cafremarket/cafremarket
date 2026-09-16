<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendorShop;
use App\Http\Controllers\Controller;
use App\Http\Resources\CancelationResource;
use App\Models\Cancellation;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderCancellationController extends Controller
{
    use ResolvesVendorShop;

    /**
     * All cancellation resquets
     *
     * @return void
     */
    public function index()
    {
        $cancellations = Cancellation::mine()->paginate(config('mobile_app.view_listing_per_page', 8));

        return CancelationResource::collection($cancellations);
    }

    /**
     * Approve cancellation request
     *
     * @return void
     */
    public function approve_request(Request $request, Order $order)
    {
        $this->assertOwnsShop((int) $order->shop_id);

        try {
            DB::beginTransaction();
            if ($order->cancellation) {
                $order->cancellation->forceFill([
                    'items' => null,
                    'status' => Cancellation::STATUS_APPROVED,
                ])->save();

                if (! $order->isCanceled()) {
                    $order->cancel(false);
                }

                DB::commit();

                return response()->json(['message' => trans('api.order_updated_successfully')], 200);
            }
            DB::rollBack();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.something_went_wrong')], 400);
    }

    /**
     * Approve cancellation request
     *
     * @return void
     */
    public function decline_request(Request $request, Order $order)
    {
        $this->assertOwnsShop((int) $order->shop_id);

        try {
            if ($order->cancellation) {
                $order->cancellation->forceFill([
                    'items' => null,
                    'status' => Cancellation::STATUS_DECLINED,
                ])->save();

                return response()->json(['message' => trans('api.order_updated_successfully')], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.something_went_wrong')], 400);
    }

    /**
     * Cancel order (vendor). Always cancels immediately so mobile/admin lists stay in sync.
     *
     * @return \Illuminate\Http\Response
     */
    public function cancel(Request $request, Order $order)
    {
        $this->assertOwnsShop((int) $order->shop_id);

        if ($order->isCanceled()) {
            return response()->json(['message' => trans('api.order_canceled')], 200);
        }

        if ($order->isDelivered()) {
            return response()->json(['message' => trans('api.order_cant_be_canceled')], 422);
        }

        DB::beginTransaction();
        try {
            if ($order->cancellation) {
                $order->cancellation->forceFill([
                    'items' => null,
                    'status' => Cancellation::STATUS_APPROVED,
                ])->save();
            }

            $order->cancel(false);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 400);
        }

        DB::commit();

        return response()->json(['message' => trans('api.order_canceled')], 200);
    }
}
