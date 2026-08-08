<?php

namespace App\Listeners\Chat;

use App\Events\Chat\NewMessageEvent;
use App\Notifications\Chat\NewMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAssociatedUsers implements ShouldQueue
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
    public function handle(NewMessageEvent $event)
    {
        $repliable = $event->msg_obj->repliable ?? $event->msg_obj;

        if ($event->msg_obj->customer_id) {
            $associate = $repliable->shop;
            $sender = $repliable->customer->getName();
            $receipent = $associate->getName();

            try {
                safe_notify($associate, new NewMessage($receipent, $sender, $event->text, $repliable), 'chat new message');
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
