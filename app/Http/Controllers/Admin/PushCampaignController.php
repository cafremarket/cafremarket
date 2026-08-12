<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendPushCampaignJob;
use App\Models\Customer;
use App\Models\DeliveryBoy;
use App\Models\PushCampaign;
use App\Models\User;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushCampaignController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->authorizeAdmin();

        $campaigns = PushCampaign::query()->latest()->paginate(20);
        $status = FCMService::driverStatus();
        $counts = [
            'customers' => Customer::whereNotNull('fcm_token')->where('fcm_token', '!=', '')->count(),
            'vendors' => User::whereNotNull('fcm_token')->where('fcm_token', '!=', '')->count(),
            'delivery' => DeliveryBoy::whereNotNull('fcm_token')->where('fcm_token', '!=', '')->count(),
        ];

        return view('admin.push_campaign.index', compact('campaigns', 'status', 'counts'));
    }

    public function create()
    {
        $this->authorizeAdmin();

        return view('admin.push_campaign._create', [
            'audiences' => PushCampaign::audienceOptions(),
            'types' => PushCampaign::typeOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $this->validated($request);
        $data['status'] = PushCampaign::STATUS_DRAFT;
        $data['created_by'] = Auth::id();

        $campaign = PushCampaign::create($data);

        if ($request->boolean('send_now')) {
            return $this->dispatchSend($campaign);
        }

        return redirect()
            ->route('admin.promotion.push_campaign.index')
            ->with('success', 'Push campaign saved as draft.');
    }

    public function show(PushCampaign $push_campaign)
    {
        $this->authorizeAdmin();

        return view('admin.push_campaign._show', [
            'campaign' => $push_campaign,
        ]);
    }

    public function edit(PushCampaign $push_campaign)
    {
        $this->authorizeAdmin();

        if (! in_array($push_campaign->status, [PushCampaign::STATUS_DRAFT, PushCampaign::STATUS_FAILED], true)) {
            return back()->with('warning', 'Only draft or failed campaigns can be edited.');
        }

        return view('admin.push_campaign._edit', [
            'campaign' => $push_campaign,
            'audiences' => PushCampaign::audienceOptions(),
            'types' => PushCampaign::typeOptions(),
        ]);
    }

    public function update(Request $request, PushCampaign $push_campaign)
    {
        $this->authorizeAdmin();

        if (! in_array($push_campaign->status, [PushCampaign::STATUS_DRAFT, PushCampaign::STATUS_FAILED], true)) {
            return back()->with('warning', 'Only draft or failed campaigns can be updated.');
        }

        $push_campaign->update($this->validated($request));

        if ($request->boolean('send_now')) {
            return $this->dispatchSend($push_campaign->fresh());
        }

        return redirect()
            ->route('admin.promotion.push_campaign.index')
            ->with('success', 'Push campaign updated.');
    }

    public function send(PushCampaign $push_campaign)
    {
        $this->authorizeAdmin();

        return $this->dispatchSend($push_campaign);
    }

    public function destroy(PushCampaign $push_campaign)
    {
        $this->authorizeAdmin();
        $push_campaign->delete();

        return back()->with('success', 'Push campaign deleted.');
    }

    protected function dispatchSend(PushCampaign $campaign)
    {
        $campaign->markQueued();
        SendPushCampaignJob::dispatch($campaign->id);

        return redirect()
            ->route('admin.promotion.push_campaign.index')
            ->with('success', 'Push campaign queued. It will send shortly.');
    }

    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:500',
            'image_url' => 'nullable|url|max:500',
            'audience' => 'required|in:customers,vendors,delivery,all',
            'type' => 'required|in:promotion,announcement,custom',
            'deep_link' => 'nullable|string|max:255',
        ]);

        $validated['data'] = array_filter([
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ]);

        return $validated;
    }

    protected function authorizeAdmin(): void
    {
        abort_unless(Auth::user() && Auth::user()->isAdmin(), 403);
    }
}
