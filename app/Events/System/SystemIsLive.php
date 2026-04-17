<?php

namespace App\Events\System;

use App\Models\System;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemIsLive
{
    use Dispatchable, SerializesModels;

    public $system;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(System $system)
    {
        $this->system = $system;
    }
}
