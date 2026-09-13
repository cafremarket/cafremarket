<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateDisputeRequest;
use App\Http\Requests\Validations\DisputeDetailRequest;
use App\Http\Requests\Validations\OrderDetailRequest;
use App\Http\Requests\Validations\ReplyDisputeRequest;
use App\Http\Resources\DisputeFormResource;
use App\Http\Resources\DisputeLightResource;
use App\Http\Resources\DisputeResource;
use App\Models\Dispute;
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

    public function index(Request $request)
    {
        $disputes = Auth::guard('api')->user()->disputes()
            ->with(['shop:id,name,slug', 'order.inventories:product_id,slug', 'order.inventories.image'])
            ->paginate(config('mobile_app.view_listing_per_page', 8));

        return DisputeLightResource::collection($disputes);
    }

    public function create(OrderDetailRequest $request, Order $order)
    {
        return new DisputeFormResource($order);
    }

    public function store(CreateDisputeRequest $request, Order $order)
    {
        $dispute = $this->tickets->createFromOrder(
            $order,
            $request->all(),
            Dispute::RAISED_BY_CUSTOMER,
            $request->file('attachments')
        );

        return new DisputeResource($dispute);
    }

    public function show(DisputeDetailRequest $request, Dispute $dispute)
    {
        return new DisputeResource($dispute->load('shop:id,name,slug'));
    }

    public function response_form(DisputeDetailRequest $request, Dispute $dispute)
    {
        return [
            'dispute' => $dispute,
            'statuses' => \App\Helpers\ListHelper::dispute_statuses(),
        ];
    }

    public function response(ReplyDisputeRequest $request, Dispute $dispute)
    {
        $this->tickets->reply($dispute, $request, Auth::guard('api')->user());

        return new DisputeResource($dispute->fresh()->load('shop:id,name,slug'));
    }

    public function mark_as_solved(DisputeDetailRequest $request, Dispute $dispute)
    {
        $this->tickets->markResolved($dispute, Dispute::RAISED_BY_CUSTOMER);

        return response()->json(trans('theme.notify.dispute_resolved') ?? trans('theme.notify.dispute_updated'), 200);
    }

    public function request_close(DisputeDetailRequest $request, Dispute $dispute)
    {
        $this->tickets->requestClose($dispute, Dispute::RAISED_BY_CUSTOMER);

        return response()->json(trans('theme.notify.dispute_close_requested') ?? trans('theme.notify.dispute_updated'), 200);
    }

    public function appeal(ReplyDisputeRequest $request, Dispute $dispute)
    {
        if (! $dispute->isResolved()) {
            $this->tickets->markResolved($dispute, Dispute::RAISED_BY_CUSTOMER);
        }

        if ($request->filled('reply')) {
            $this->tickets->reply($dispute->fresh(), $request, Auth::guard('api')->user());
        }

        $this->tickets->requestClose($dispute->fresh(), Dispute::RAISED_BY_CUSTOMER);

        return new DisputeResource($dispute->fresh()->load('shop:id,name,slug'));
    }
}
