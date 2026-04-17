<?php

namespace App\Listeners\Customer;

use App\Events\Customer\Registered;
use App\Notifications\Customer\Registered as CustomerRegister;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWelcomeEmail implements ShouldQueue
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
    public function handle(Registered $event)
    {
        $event->customer->notify(new CustomerRegister($event->customer));
    }
}
