<?php

namespace App\Listeners\Customer;

use App\Events\Customer\CustomerProfileUpdated;
use App\Notifications\Customer\ProfileUpdated as ProfileUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendProfileUpdateNotification implements ShouldQueue
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
    public function handle(CustomerProfileUpdated $event)
    {
        safe_notify($event->customer, new ProfileUpdatedNotification($event->customer), 'customer profile updated');
    }
}
