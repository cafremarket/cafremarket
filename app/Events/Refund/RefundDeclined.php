<?php

namespace App\Events\Refund;

use App\Models\Refund;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundDeclined
{
    use Dispatchable, SerializesModels;

    public $refund;

    public $notify_customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Refund $refund, $notify_customer = null)
    {
        $this->refund = $refund;
        $this->notify_customer = $notify_customer;
    }
}
