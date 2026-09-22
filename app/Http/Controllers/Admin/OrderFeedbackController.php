<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderFeedbackController extends Controller
{
    /**
     * List customers' order feedbacks. Merchants only see their own shop's.
     */
    public function index(Request $request)
    {
        $this->authorize('index', Order::class);

        $query = OrderFeedback::query()
            ->when(Auth::user()->isFromMerchant(), fn ($q) => $q->where('shop_id', Auth::user()->merchantId()))
            ->when($request->filled('rating'), fn ($q) => $q->where('rating', (int) $request->input('rating')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->whereHas('order', fn ($o) => $o->where('order_number', 'like', '%'.$request->input('q').'%'));
            });

        $average = round((float) (clone $query)->avg('rating'), 1);

        $feedbacks = $query->with(['order:id,order_number', 'customer', 'shop:id,name'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.order.feedbacks', compact('feedbacks', 'average'));
    }
}
