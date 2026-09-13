<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateDisputeRequest;
use App\Http\Requests\Validations\ResponseDisputeRequest;
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
        parent::__construct();
        $this->tickets = $tickets;
    }

    public function index()
    {
        $this->authorize('index', Dispute::class);

        $shopId = Auth::user()->merchantId();

        $base = Dispute::with('dispute_type', 'order', 'customer')
            ->withCount('replies')
            ->where('shop_id', $shopId)
            ->orderByDesc('updated_at');

        $open = (clone $base)->open()->get();
        $closed = (clone $base)->closed()->get();

        return view('merchant.dispute.index', compact('open', 'closed'));
    }

    public function show(Dispute $dispute)
    {
        $this->authorize('view', $dispute);
        $this->authorizeShop($dispute);

        $dispute->load([
            'dispute_type',
            'order.inventories',
            'customer',
            'replies.attachments',
            'replies.user',
            'replies.customer',
            'attachments',
        ]);

        $statuses = \App\Helpers\ListHelper::dispute_statuses();

        return view('merchant.dispute.show', compact('dispute', 'statuses'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Dispute::class);

        $orders = Order::mine()
            ->with('customer')
            ->whereDoesntHave('dispute')
            ->latest()
            ->get(['id', 'order_number', 'customer_id', 'grand_total', 'currency_id', 'shop_id']);

        $types = DisputeType::orderBy('id')->pluck('detail', 'id');
        $selectedOrderId = $request->query('order_id');

        return view('merchant.dispute.create', compact('orders', 'types', 'selectedOrderId'));
    }

    public function store(CreateDisputeRequest $request, Order $order)
    {
        $this->authorize('create', Dispute::class);
        $this->authorizeOrder($order);

        $dispute = $this->tickets->createFromOrder(
            $order,
            $request->all(),
            Dispute::RAISED_BY_VENDOR,
            $request->file('attachments')
        );

        return redirect()
            ->route('merchant.support.dispute.show', $dispute)
            ->with('success', trans('theme.notify.dispute_created') ?? 'Dispute ticket created.');
    }

    public function response(ResponseDisputeRequest $request, Dispute $dispute)
    {
        $this->authorize('response', $dispute);
        $this->authorizeShop($dispute);

        $this->tickets->reply($dispute, $request, Auth::user());

        return back()->with('success', trans('theme.notify.dispute_updated') ?? 'Ticket updated.');
    }

    public function markResolved(Dispute $dispute)
    {
        $this->authorize('response', $dispute);
        $this->authorizeShop($dispute);

        $this->tickets->markResolved($dispute, Dispute::RAISED_BY_VENDOR);

        return back()->with('success', trans('theme.notify.dispute_updated') ?? 'Ticket marked as resolved.');
    }

    public function requestClose(Dispute $dispute)
    {
        $this->authorize('response', $dispute);
        $this->authorizeShop($dispute);

        $this->tickets->requestClose($dispute, Dispute::RAISED_BY_VENDOR);

        return back()->with('success', trans('theme.notify.dispute_close_requested') ?? 'Close request sent to admin.');
    }

    protected function authorizeShop(Dispute $dispute): void
    {
        if ((int) $dispute->shop_id !== (int) Auth::user()->merchantId()) {
            abort(403);
        }
    }

    protected function authorizeOrder(Order $order): void
    {
        if ((int) $order->shop_id !== (int) Auth::user()->merchantId()) {
            abort(403);
        }
    }
}
