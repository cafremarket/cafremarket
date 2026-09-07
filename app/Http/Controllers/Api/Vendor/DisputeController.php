<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Events\Dispute\DisputeCreated;
use App\Events\Dispute\DisputeUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateDisputeRequest;
use App\Http\Requests\Validations\ResponseDisputeRequest;
use App\Http\Resources\DisputeResource;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\System;
use App\Notifications\SuperAdmin\AppealedDisputeReplied as AppealedDisputeRepliedNotification;
use App\Notifications\SuperAdmin\DisputeAppealed as DisputeAppealedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DisputeController extends Controller
{
    /**
     * All disoutes
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $disputes = Dispute::mine()->get();

        $disputes = $disputes->paginate(config('mobile_app.view_listing_per_page', 8));

        return DisputeResource::collection($disputes);
    }

    /**
     * Vendor raises a dispute ticket on an order (ticket system, not chat).
     */
    public function store(CreateDisputeRequest $request, Order $order)
    {
        if ($order->dispute) {
            return response()->json([
                'message' => 'A dispute ticket already exists for this order.',
            ], 422);
        }

        $payload = $request->all();
        $payload['raised_by'] = Dispute::RAISED_BY_VENDOR;
        $payload['status'] = Dispute::STATUS_NEW;

        $dispute = $order->dispute()->create($payload);

        if ($request->hasFile('attachments')) {
            $dispute->saveAttachments($request->file('attachments'));
        }

        event(new DisputeCreated($dispute));

        return new DisputeResource($dispute->load('shop:id,name,slug'));
    }

    public function show(Request $request, Dispute $dispute)
    {
        // Check Permission

        return new DisputeResource($dispute);
    }

    /**
     * Display the response form.
     *
     * @return \Illuminate\Http\Response
     */
    public function response(ResponseDisputeRequest $request, Dispute $dispute)
    {
        // $dispute = $this->dispute->find($id);

        try {
            $old_status = $dispute->status;

            $dispute->update($request->all());

            $response = $dispute->replies()->create($request->all());

            if ($request->hasFile('attachments')) {
                $response->saveAttachments($request->file('attachments'));
            }

            $current_status = $response->repliable->status;

            // Send notification to Admin
            if (config('system_settings.notify_when_dispute_appealed') && ($current_status == Dispute::STATUS_APPEALED)) {
                $system = System::orderBy('id', 'asc')->first();

                if ($current_status != $old_status) {
                    safe_notify($system->superAdmin(), new DisputeAppealedNotification($response), 'vendor dispute appealed');
                } else {
                    safe_notify($system->superAdmin(), new AppealedDisputeRepliedNotification($response), 'vendor dispute reply');
                }
            }

            event(new DisputeUpdated($response));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.dispute_updated_successfully')], 200);
    }
}
