<?php

namespace App\Listeners\User;

use App\Events\User\UserCreated;
use App\Notifications\User\SendLoginInfo as UserCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendLoginInfo implements ShouldQueue
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
    public function handle(UserCreated $event)
    {
        try {
            $event->user->notify(new UserCreatedNotification($event->user, $event->admin, $event->password));
        } catch (Throwable $e) {
            Log::warning('User created but login email could not be sent.', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
