<?php

namespace App\Listeners\Ticket;

use App\Events\Ticket\TicketReplied;
use App\Notifications\Ticket\TicketReplied as TicketRepliedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAssociatedUsersTicketReplied implements ShouldQueue
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
    public function handle(TicketReplied $event)
    {
        if ($event->reply->user->isFromPlatform()) {
            safe_notify($event->reply->repliable->user, new TicketRepliedNotification($event->reply, $event->reply->repliable->user->getName()), 'ticket replied user');
        } elseif ($event->reply->repliable->assignedTo) {
            safe_notify($event->reply->repliable->assignedTo, new TicketRepliedNotification($event->reply, $event->reply->repliable->assignedTo->getName()), 'ticket replied assignee');
        }
    }
}
