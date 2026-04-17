<?php

namespace App\Listeners\Profile;

use App\Events\Profile\ProfileUpdated;
use App\Notifications\User\ProfileUpdated as ProfileUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyUserProfileUpdated implements ShouldQueue
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
    public function handle(ProfileUpdated $event)
    {
        $event->user->notify(new ProfileUpdatedNotification($event->user));
    }
}
