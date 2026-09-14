<?php

namespace App\Listeners\Dispute;

use App\Events\Dispute\DisputeUpdated;
use App\Notifications\Dispute\Updated as DisputeUpdatedNotification;
use App\Services\FCMService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

class NotifyCustomerDisputeUpdated implements ShouldQueue
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
    public function handle(DisputeUpdated $event)
    {
        $reply = $event->reply;
        $dispute = $reply->repliable;

        if (! $dispute) {
            return;
        }

        $dispute->loadMissing(['customer', 'shop', 'order']);

        $orderNumber = optional($dispute->order)->order_number;
        $preview = Str::limit(trim(strip_tags((string) $reply->reply)), 120);
        if ($preview === '') {
            $preview = trans('notifications.dispute_updated.message', ['order_id' => $orderNumber]);
        }

        $data = [
            'type' => 'dispute',
            'dispute_id' => (int) $dispute->id,
            'order_id' => (int) optional($dispute->order)->id,
            'order_number' => (string) $orderNumber,
        ];

        $title = trans('notifications.dispute_updated.subject', ['order_id' => $orderNumber]);

        if ($reply->customer_id) {
            // Customer replied → notify vendor app
            FCMService::sendToShop($dispute->shop, [
                'title' => $title,
                'body' => $preview,
            ], $data);
        } else {
            // Merchant/admin replied → notify customer app
            $customerToken = optional($dispute->customer)->fcm_token;

            if ($customerToken) {
                FCMService::send($customerToken, [
                    'title' => $title,
                    'body' => trans('notifications.dispute_updated.message', ['order_id' => $orderNumber]),
                ], 'customer', $data);
            }

            if ($dispute->customer) {
                safe_notify($dispute->customer, new DisputeUpdatedNotification($reply), 'dispute updated');
            }
        }
    }
}
