<?php

namespace App\Http\Controllers\Storefront;

// use Carbon\Carbon;
use App\Events\Order\OrderCancellationRequestCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\OrderCancellationRequest;
use App\Http\Requests\Validations\OrderDetailRequest;
use App\Models\CancellationReason;
use App\Models\Order;

class OrderCancelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showForm(OrderDetailRequest $request, Order $order, $action = 'cancel')
    {
        // Cancel-items requests are temporarily disabled; returns remain available.
        if ($action === 'cancel') {
            return redirect()->back()->with('error', trans('theme.cancel_items_disabled'));
        }

        $reasons = CancellationReason::orderBy('id')->front()->pluck('detail', 'id');

        return view('theme::modals._item_cancel', compact('order', 'reasons', 'action'))->render();
    }

    /**
     * Cancel the order and revert the items into available stock
     */
    public function saveCancelRequest(OrderCancellationRequest $request, Order $order)
    {
        if (($request->input('action') ?: 'cancel') !== 'return') {
            return redirect()->back()->with('error', trans('theme.cancel_items_disabled'));
        }

        if ($order->cancellation) {
            $order->cancellation->update($request->all());

            // Reset previous status
            $order->cancellation->resetStatus();
        } else {
            $order->cancellation()->create($request->all());
        }

        event(new OrderCancellationRequestCreated($order));

        return redirect()->back()->with('success', trans('theme.order_cancellation_requested'));
    }

    /**
     * Cancel the order and revert the items into available stock
     */
    public function cancel(OrderDetailRequest $request, Order $order)
    {
        $order->cancel();

        return redirect()->back()->with('success', trans('theme.order_canceled'));
    }
}
