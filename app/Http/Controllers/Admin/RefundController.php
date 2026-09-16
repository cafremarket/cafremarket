<?php

namespace App\Http\Controllers\Admin;

use App\Common\Authorizable;
use App\Events\Refund\RefundApproved;
use App\Events\Refund\RefundDeclined;
use App\Events\Refund\RefundInitiated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\InitiateRefundRequest;
use App\Models\Refund;
use App\Repositories\Refund\RefundRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Incevio\Package\Wallet\Services\CommonService as WalletService;

class RefundController extends Controller
{
    use Authorizable;

    private $model_name;

    private $refund;

    /**
     * construct
     */
    public function __construct(RefundRepository $refund)
    {
        parent::__construct();

        $this->model_name = trans('app.model.refund');

        $this->refund = $refund;
    }

    /**
     * Display a listing of the resource (top-level Refunds module).
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Sync refunded orders that never got a refunds row (pre-module cancels).
        // Idempotent: only creates rows for orders with no refunds yet.
        try {
            \App\Models\Order::backfillMissingRefundRecords();
        } catch (\Throwable $e) {
            \Log::warning('Refunds backfill skipped: '.$e->getMessage());
        }

        $tab = $request->get('tab', $request->get('status', 'pending'));

        // Legacy open/closed query values map onto the new tabs.
        $tab = match ($tab) {
            'open', 'new' => 'pending',
            'closed', 'approved' => 'completed',
            'declined', 'failed' => 'issue',
            default => $tab,
        };

        if (! in_array($tab, ['pending', 'completed', 'issue'], true)) {
            $tab = 'pending';
        }

        $search = trim((string) $request->get('q', ''));

        $refunds = $this->refund->forTab($tab, $search !== '' ? $search : null);

        $counts = [
            'pending' => $this->refund->pending()->count(),
            'completed' => $this->refund->completed()->count(),
            'issue' => $this->refund->issue()->count(),
        ];

        return view('admin.refunds.index', compact('refunds', 'tab', 'search', 'counts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  int  $order
     * @return \Illuminate\Http\Response
     */
    public function showRefundForm($order = null)
    {
        if ($order) {
            $order = $this->refund->findOrder($order);
        }

        // Initiate always opens Pending — completion only via Approve.
        $statuses = [
            Refund::STATUS_NEW => trans('app.refund_status.pending'),
        ];

        return view('admin.refunds._initiate', compact('order', 'statuses'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function initiate(InitiateRefundRequest $request)
    {
        // Never auto-complete on initiate — admin must approve or reject.
        $request->merge(['status' => Refund::STATUS_NEW]);

        DB::beginTransaction();
        try {
            $refund = $this->refund->store($request);

            $wallet = new WalletService;

            if (auth()->user()->isFromMerchant()) {
                $shop = auth()->user()->shop;
            } else {
                $shop = $refund->shop;
            }

            $wallet->verifyWithdraw($shop, $request->amount);

            event(new RefundInitiated($refund, $request->filled('notify_customer')));
        } catch (\Exception $e) {
            \Log::error($e);

            DB::rollback();

            return redirect()->back()->with('error', $e->getMessage());
        }

        DB::commit();

        return back()->with('success', trans('messages.created', ['model' => $this->model_name]));
    }

    /**
     * Display the response / approve-decline modal.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function response($id)
    {
        $refund = $this->refund->find($id);

        return view('admin.refunds._response', compact('refund'));
    }

    public function approve(Request $request, $id)
    {
        $refund = $this->refund->approve($id);

        try {
            $this->refund_to_wallet($refund);
        } catch (\Exception $e) {
            $this->refund->markIssue($refund, $e->getMessage());

            return back()->with('error', $e->getMessage());
        }

        event(new RefundApproved($refund, $request->filled('notify_customer')));

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    public function decline(Request $request, $id)
    {
        $adminNote = trim((string) $request->input('admin_note', ''));
        $refund = $this->refund->decline($id, $adminNote !== '' ? $adminNote : null);

        event(new RefundDeclined($refund, $request->filled('notify_customer')));

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    public function markIssue(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'required|string|min:3|max:500',
        ]);

        $this->refund->markIssue($id, trim($request->input('admin_note')));

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    private function refund_to_wallet($refund)
    {
        if ($refund->isApproved() && $refund->order->customer_id && customer_has_wallet()) {
            $wallet = new \Incevio\Package\Wallet\Services\RefundToWallet;

            return $wallet->sender($refund->shop)
                ->receiver($refund->order->customer)
                ->amount($refund->amount)
                ->meta([
                    'type' => trans('packages.wallet.refund'),
                    'description' => trans('packages.wallet.refund_of', ['order' => $refund->order->order_number]),
                ])
                ->forceTransfer()
                ->execute();
        }
    }
}
