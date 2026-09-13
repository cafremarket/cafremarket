<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateDisputeRequest;
use App\Http\Requests\Validations\ResponseDisputeRequest;
use App\Http\Resources\DisputeFormResource;
use App\Http\Resources\DisputeResource;
use App\Models\Dispute;
use App\Models\DisputeType;
use App\Models\Order;
use App\Services\Dispute\DisputeTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DisputeController extends Controller
{
    private $tickets;

    public function __construct(DisputeTicketService $tickets)
    {
        $this->tickets = $tickets;
    }

    public function index()
    {
        $disputes = Dispute::mine()
            ->with(['dispute_type', 'order', 'customer', 'shop'])
            ->latest()
            ->paginate(config('mobile_app.view_listing_per_page', 8));

        return DisputeResource::collection($disputes);
    }

    public function create()
    {
        $orders = Order::mine()
            ->with('customer')
            ->whereDoesntHave('dispute')
            ->latest()
            ->limit(50)
            ->get(['id', 'order_number', 'customer_id', 'grand_total', 'currency_id', 'shop_id']);

        return response()->json([
            'data' => [
                'orders' => $orders->map(fn (Order $order) => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => optional($order->customer)->name,
                    'grand_total' => get_formated_currency($order->grand_total, 2, $order->currency_id),
                ])->values(),
                'dispute_type' => DisputeType::orderBy('id')->pluck('detail', 'id'),
            ],
        ]);
    }

    public function form(Order $order)
    {
        if ((int) $order->shop_id !== (int) Auth::user()->merchantId()) {
            abort(403);
        }

        return new DisputeFormResource($order);
    }

    public function store(CreateDisputeRequest $request, Order $order)
    {
        if ((int) $order->shop_id !== (int) Auth::user()->merchantId()) {
            abort(403);
        }

        $dispute = $this->tickets->createFromOrder(
            $order,
            $request->all(),
            Dispute::RAISED_BY_VENDOR,
            $request->file('attachments')
        );

        return new DisputeResource($dispute->load('shop:id,name,slug'));
    }

    public function show(Request $request, Dispute $dispute)
    {
        if ((int) $dispute->shop_id !== (int) Auth::user()->merchantId()) {
            abort(403);
        }

        $dispute->load([
            'dispute_type',
            'order.inventories.image',
            'customer.avatarImage',
            'attachments',
            'replies.attachments',
            'replies.user',
            'replies.customer',
        ]);

        return new DisputeResource($dispute);
    }

    public function response(ResponseDisputeRequest $request, Dispute $dispute)
    {
        if ((int) $dispute->shop_id !== (int) Auth::user()->merchantId()) {
            abort(403);
        }

        try {
            $this->tickets->reply($dispute, $request, Auth::user());
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.dispute_updated_successfully')], 200);
    }

    public function markResolved(Request $request, Dispute $dispute)
    {
        if ((int) $dispute->shop_id !== (int) Auth::user()->merchantId()) {
            abort(403);
        }

        $this->tickets->markResolved($dispute, Dispute::RAISED_BY_VENDOR);

        return response()->json(['message' => trans('api.dispute_updated_successfully')], 200);
    }

    public function requestClose(Request $request, Dispute $dispute)
    {
        if ((int) $dispute->shop_id !== (int) Auth::user()->merchantId()) {
            abort(403);
        }

        $this->tickets->requestClose($dispute, Dispute::RAISED_BY_VENDOR);

        return response()->json(['message' => trans('theme.notify.dispute_close_requested') ?? 'Close request sent to admin.'], 200);
    }
}
