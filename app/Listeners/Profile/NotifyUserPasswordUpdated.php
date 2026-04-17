<?php

namespace App\Listeners\Profile;

use App\Events\Profile\PasswordUpdated;
use App\Notifications\User\PasswordUpdated as PasswordUpdateNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyUserPasswordUpdated implements ShouldQueue
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
    public function handle(PasswordUpdated $event)
    {
        $event->user->notify(new PasswordUpdateNotification($event->user));
    }
}
