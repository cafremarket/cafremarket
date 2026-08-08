<?php

namespace App\Listeners\Dispute;

use App\Events\Dispute\DisputeSolved;
use App\Notifications\Dispute\Solved as DisputeSolvedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerDisputeSolved implements ShouldQueue
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
    public function handle(DisputeSolved $event)
    {
        safe_notify($event->dispute->customer, new DisputeSolvedNotification($event->dispute), 'dispute solved');
    }
}
