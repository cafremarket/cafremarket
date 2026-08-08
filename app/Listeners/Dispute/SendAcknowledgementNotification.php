<?php

namespace App\Listeners\Dispute;

use App\Events\Dispute\DisputeCreated;
use App\Notifications\Dispute\SendAcknowledgement as AcknowledgementNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAcknowledgementNotification implements ShouldQueue
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
    public function handle(DisputeCreated $event)
    {
        safe_notify($event->dispute->customer, new AcknowledgementNotification($event->dispute), 'dispute acknowledgement');
    }
}
