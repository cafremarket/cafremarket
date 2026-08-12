<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\DeliveryBoy;
use App\Models\PushCampaign;
use App\Models\User;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 600;

    protected int $campaignId;

    public function __construct(int $campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function handle(): void
    {
        $campaign = PushCampaign::find($this->campaignId);
        if (! $campaign) {
            return;
        }

        $campaign->status = PushCampaign::STATUS_SENDING;
        $campaign->save();

        $notification = array_filter([
            'title' => $campaign->title,
            'body' => $campaign->body,
            'image' => $campaign->image_url,
        ]);

        $data = array_merge($campaign->data ?? [], [
            'type' => $campaign->type ?: 'promotion',
            'campaign_id' => (string) $campaign->id,
            'deep_link' => (string) ($campaign->deep_link ?? ''),
        ]);

        $sent = 0;
        $failed = 0;
        $targets = 0;

        try {
            foreach ($this->audiencesFor($campaign->audience) as $fcmAudience => $tokens) {
                $list = $tokens->filter()->unique()->values();
                $targets += $list->count();
                $chunk = max(1, (int) config('fcm.campaign_chunk', 80));

                foreach ($list->chunk($chunk) as $batch) {
                    $result = FCMService::sendToMany($batch, $notification, $fcmAudience, $data);
                    $sent += $result['sent'];
                    $failed += $result['failed'];
                }
            }

            $campaign->target_count = $targets;
            $campaign->sent_count = $sent;
            $campaign->failed_count = $failed;
            $campaign->sent_at = now();
            $campaign->status = $sent > 0
                ? PushCampaign::STATUS_SENT
                : PushCampaign::STATUS_FAILED;
            if ($sent === 0) {
                $campaign->error_message = 'No devices received the notification. Check FCM credentials and that users have logged in recently.';
            }
            $campaign->save();
        } catch (\Throwable $e) {
            Log::error('Push campaign failed: '.$e->getMessage(), [
                'campaign_id' => $campaign->id,
            ]);
            $campaign->status = PushCampaign::STATUS_FAILED;
            $campaign->error_message = $e->getMessage();
            $campaign->save();
        }
    }

    /**
     * @return array<string, \Illuminate\Support\Collection>
     */
    protected function audiencesFor(string $audience): array
    {
        $map = [];

        if (in_array($audience, [PushCampaign::AUDIENCE_CUSTOMERS, PushCampaign::AUDIENCE_ALL], true)) {
            $map['customer'] = Customer::query()
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token');
        }

        if (in_array($audience, [PushCampaign::AUDIENCE_VENDORS, PushCampaign::AUDIENCE_ALL], true)) {
            $map['vendor'] = User::query()
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token');
        }

        if (in_array($audience, [PushCampaign::AUDIENCE_DELIVERY, PushCampaign::AUDIENCE_ALL], true)) {
            $map['delivery'] = DeliveryBoy::query()
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token');
        }

        return $map;
    }
}
