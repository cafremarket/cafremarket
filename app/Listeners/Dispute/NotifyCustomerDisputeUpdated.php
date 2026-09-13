<?php

namespace App\Listeners\Dispute;

use App\Events\Dispute\DisputeUpdated;
use App\Notifications\Dispute\Updated as DisputeUpdatedNotification;
use App\Services\FCMService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerDisputeUpdated implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(DisputeUpdated $event)
    {
        if ($event->reply->customer_id) {
            return;
        }

        $repliable = $event->reply->repliable;
        $customer_token = optional($repliable->customer)->fcm_token;

        if (! is_null($customer_token)) {
            FCMService::send($customer_token, [
                'title' => trans('notifications.dispute_updated.subject', ['order_id' => $repliable->order->order_number]),
                'body' => trans('notifications.dispute_updated.message', ['order_id' => $repliable->order->order_number]),
            ]);
        }

        safe_notify($event->reply->repliable->customer, new DisputeUpdatedNotification($event->reply), 'dispute updated');
    }
}
