<?php

namespace App\Listeners\System;

use App\Events\System\SystemIsLive;
use App\Notifications\System\SystemIsLive as SystemIsLiveNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAdminSystemIsLive implements ShouldQueue
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
    public function handle(SystemIsLive $event)
    {
        safe_notify($event->system->superAdmin(), new SystemIsLiveNotification($event->system), 'system is live');
    }
}
