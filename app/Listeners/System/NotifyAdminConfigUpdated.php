<?php

namespace App\Listeners\System;

use App\Events\System\SystemConfigUpdated;
use App\Notifications\System\SystemConfigUpdated as SystemConfigUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAdminConfigUpdated implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 10;

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
    public function handle(SystemConfigUpdated $event)
    {
        safe_notify($event->system->superAdmin(), new SystemConfigUpdatedNotification($event->system), 'system config updated');
    }
}
