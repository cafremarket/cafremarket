<?php

namespace App\Listeners\Customer;

use App\Events\Customer\Registered;
use App\Models\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyCustomerAccount implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    public $user;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(Registered $event)
    {
        // $request->user()->markEmailAsVerified();

        $customer = Customer::where('email', $this->user->email)->first();

        $customer->verification_token = null;

        $customer->save();
    }
}
