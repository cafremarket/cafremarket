<?php

namespace App\Listeners\Announcement;

use App\Events\Announcement\AnnouncementCreated;

class SendAnnouncementCreatedNotification
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
    public function handle(AnnouncementCreated $event)
    {
        //
    }
}
