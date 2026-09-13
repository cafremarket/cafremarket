<?php

namespace App\Http\Controllers\Admin;

use App\Common\Authorizable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\ResponseDisputeRequest;
use App\Models\Dispute;
use App\Services\Dispute\DisputeTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DisputeController extends Controller
{
    use Authorizable;

    private $model_name;

    private $tickets;

    public function __construct(DisputeTicketService $tickets)
    {
        parent::__construct();

        $this->model_name = trans('app.model.dispute');
        $this->tickets = $tickets;
    }

    public function index()
    {
        $query = Dispute::with('dispute_type', 'order', 'customer.avatarImage', 'shop')
            ->withCount('replies')
            ->orderByDesc('updated_at');

        if (! Auth::user()->isFromPlatform()) {
            $query->mine();
        }

        $pendingClose = (clone $query)->closeRequested()->get();
        $disputes = (clone $query)->open()->where('status', '!=', Dispute::STATUS_CLOSE_REQUESTED)->get();
        $closed = (clone $query)->closed()->get();

        return view('admin.dispute.index', compact('disputes', 'pendingClose', 'closed'));
    }

    public function show($id)
    {
        $dispute = Dispute::with([
            'activities.causer',
            'dispute_type',
            'order.refunds',
            'customer',
            'shop.owner',
            'product.image',
            'replies.attachments',
            'replies.user',
            'replies.customer',
            'attachments',
            'closedByUser',
        ])->findOrFail($id);

        return view('admin.dispute.show', compact('dispute'));
    }

    public function response($id)
    {
        $dispute = Dispute::findOrFail($id);

        return view('admin.dispute._response', compact('dispute'));
    }

    public function storeResponse(ResponseDisputeRequest $request, $id)
    {
        $dispute = Dispute::findOrFail($id);

        $this->tickets->reply($dispute, $request, Auth::user());

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    public function close(Request $request, Dispute $dispute)
    {
        $this->authorize('close', $dispute);

        $this->tickets->close($dispute, Auth::user());

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }
}
