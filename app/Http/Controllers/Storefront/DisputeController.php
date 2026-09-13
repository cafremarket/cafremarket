<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateDisputeRequest;
use App\Http\Requests\Validations\OrderDetailRequest;
use App\Http\Requests\Validations\ReplyDisputeRequest;
use App\Models\Dispute;
use App\Models\DisputeType;
use App\Models\Order;
use App\Services\Dispute\DisputeTicketService;
use Illuminate\Support\Facades\Auth;

class DisputeController extends Controller
{
    private $tickets;

    public function __construct(DisputeTicketService $tickets)
    {
        $this->tickets = $tickets;
    }

    public function show_dispute_form(OrderDetailRequest $request, Order $order)
    {
        $types = DisputeType::orderBy('id')->pluck('detail', 'id');

        return view('theme::dispute', compact('order', 'types'));
    }

    public function open_dispute(CreateDisputeRequest $request, Order $order)
    {
        $this->tickets->createFromOrder(
            $order,
            $request->all(),
            Dispute::RAISED_BY_CUSTOMER,
            $request->file('attachments')
        );

        return redirect()->route('order.detail', $order)
            ->with('success', trans('theme.notify.dispute_created'));
    }

    public function response(ReplyDisputeRequest $request, Dispute $dispute)
    {
        $this->tickets->reply($dispute, $request, Auth::guard('customer')->user() ?: Auth::user());

        return back()->with('success', trans('theme.notify.dispute_updated'));
    }

    public function markAsSolved(Dispute $dispute)
    {
        $this->assertCustomer($dispute);

        $this->tickets->markResolved($dispute, Dispute::RAISED_BY_CUSTOMER);

        return back()->with('success', trans('theme.notify.dispute_resolved') ?? trans('theme.notify.dispute_updated'));
    }

    public function requestClose(Dispute $dispute)
    {
        $this->assertCustomer($dispute);

        $this->tickets->requestClose($dispute, Dispute::RAISED_BY_CUSTOMER);

        return back()->with('success', trans('theme.notify.dispute_close_requested') ?? trans('theme.notify.dispute_updated'));
    }

    protected function assertCustomer(Dispute $dispute): void
    {
        $customer = Auth::guard('customer')->user() ?: Auth::user();

        if (! $customer || (int) $dispute->customer_id !== (int) $customer->id) {
            abort(403);
        }
    }
}
