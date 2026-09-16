<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Events\Refund\RefundApproved;
use App\Events\Refund\RefundDeclined;
use App\Events\Refund\RefundInitiated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\InitiateRefundRequest;
use App\Http\Resources\RefundResource;
use App\Models\Refund;
use App\Repositories\Refund\RefundRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
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
     * Display listing of refunds by tab/status.
     * Accepts: pending|completed|issue (and legacy open|closed aliases).
     *
     * @return \Illuminate\Http\Response
     */
    public function index($status = 'pending')
    {
        $refunds = Refund::mine()->with(['order']);

        switch ($status) {
            case 'completed':
                $refunds = $refunds->completed();
                break;

            case 'closed': // legacy: completed + issue
                $refunds = $refunds->closed();
                break;

            case 'issue':
                $refunds = $refunds->issue();
                break;

            case 'pending':
            case 'open': // legacy alias for pending
            default:
                $refunds = $refunds->pending();
                break;
        }

        return RefundResource::collection($refunds->latest()->get());
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Refund $refund)
    {
        return new RefundResource($refund);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function initiate(InitiateRefundRequest $request)
    {
        // Never auto-complete on initiate — admin/vendor must approve or reject.
        $request->merge(['status' => Refund::STATUS_NEW]);

        DB::beginTransaction();

        try {
            $refund = $this->refund->store($request);

            event(new RefundInitiated($refund, $request->filled('notify_customer')));
        } catch (\Exception $e) {
            \Log::error($e);

            DB::rollback();

            return response()->json(['message' => $e->getMessage()], 400);
        }

        DB::commit();

        return response()->json(['message' => trans('api.refund_has_been_created_successfully')], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function response($id)
    {
        $refund = $this->refund->find($id);

        return new RefundResource($refund);
    }

    public function approve(Request $request, $id)
    {
        $refund = $this->refund->approve($id);

        try {
            $this->refund_to_wallet($refund);
        } catch (\Exception $e) {
            $this->refund->markIssue($refund, $e->getMessage());

            return response()->json(['message' => $e->getMessage()], 400);
        }

        event(new RefundApproved($refund, $request->filled('notify_customer')));

        return response()->json(['message' => trans('api.refund_updated_successfully')]);
    }

    public function decline(Request $request, $id)
    {
        $adminNote = trim((string) $request->input('admin_note', ''));
        $refund = $this->refund->decline($id, $adminNote !== '' ? $adminNote : null);

        event(new RefundDeclined($refund, $request->filled('notify_customer')));

        return response()->json(['message' => trans('api.refund_updated_successfully')]);
    }

    public function markIssue(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'required|string|min:3|max:500',
        ]);

        $this->refund->markIssue($id, trim($request->input('admin_note')));

        return response()->json(['message' => trans('api.refund_updated_successfully')]);
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
