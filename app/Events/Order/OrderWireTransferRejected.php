<?php

namespace App\Events\Order;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderWireTransferRejected
{
    use Dispatchable, SerializesModels;

    public $order;

    /**
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
