<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Admin-only queue for verifying customer-uploaded bank transfer (wire) proofs
 * before marking the order as paid. Never mirrored into the merchant/store panel.
 */
class WireTransferController extends Controller
{
    /**
     * List orders paid via bank transfer that are still awaiting admin verification.
     */
    public function index(): View
    {
        abort_unless(Auth::user()->isFromPlatform(), 403);

        $this->authorize('index', Order::class);

        $orders = Order::query()
            ->whereHas('paymentMethod', function ($query) {
                $query->where('code', 'wire');
            })
            ->unpaid()
            ->where('order_status_id', '!=', Order::STATUS_CANCELED)
            ->with(['attachments', 'shop:id,name', 'customer', 'paymentMethod'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.order.wire_transfers', compact('orders'));
    }

    /**
     * Verify the transfer proof and mark the order as paid.
     */
    public function approve(Order $order): RedirectResponse
    {
        abort_unless(Auth::user()->isFromPlatform(), 403);

        $this->authorize('fulfill', $order);

        abort_unless(optional($order->paymentMethod)->code === 'wire', 404);

        $order->markAsPaid();

        return back()->with('success', trans('messages.updated', ['model' => trans('app.model.order')]));
    }
}
