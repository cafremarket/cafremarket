<?php

namespace App\Listeners\Customer;

use App\Events\Customer\CustomerCreated;

class SendLoginInfo
{
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
    public function handle(CustomerCreated $event)
    {
        //
    }
}
