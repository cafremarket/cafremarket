<?php

namespace App\Listeners\Dispute;

use App\Events\Dispute\DisputeCreated;
use App\Models\Dispute;
use App\Notifications\Dispute\Created as DisputeCreatedNotification;
use App\Services\FCMService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyMerchantDisputeCreated implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(DisputeCreated $event)
    {
        if ($event->dispute->raised_by === Dispute::RAISED_BY_VENDOR) {
            return;
        }

        if (! config('shop_settings')) {
            setShopConfig($event->dispute->shop_id);
        }

        $dispute = $event->dispute;
        $dispute->loadMissing(['order', 'shop', 'customer']);
        $orderNumber = optional($dispute->order)->order_number;

        // Always push to vendor app (independent of email shop setting)
        FCMService::sendToShop($dispute->shop, [
            'title' => trans('notifications.dispute_created.subject', [
                'order_id' => $orderNumber,
            ]),
            'body' => trans('notifications.dispute_created.message', [
                'order_id' => $orderNumber,
            ]),
        ], [
            'type' => 'dispute',
            'dispute_id' => (int) $dispute->id,
            'order_id' => (int) optional($dispute->order)->id,
            'order_number' => (string) $orderNumber,
        ]);

        if (config('shop_settings.notify_new_disput')) {
            safe_notify($dispute->shop, new DisputeCreatedNotification($dispute), 'dispute created merchant');
        }
    }
}
