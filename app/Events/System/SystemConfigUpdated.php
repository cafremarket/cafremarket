<?php

namespace App\Events\System;

use App\Models\SystemConfig;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SystemConfigUpdated
{
    use Dispatchable, SerializesModels;

    public $system;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SystemConfig $system)
    {
        // Clear system_settings from cache
        Cache::forget('system_settings');

        $this->system = $system;
    }
}
