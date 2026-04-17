<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view ticketes.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_ticket'))->check();
    }

    /**
     * Determine whether the user can view the Ticket.
     *
     * @return mixed
     */
    public function view(User $user, Ticket $ticket)
    {
        return (new Authorize($user, 'view_ticket', $ticket))->check();
    }

    /**
     * Determine whether the user can update the Ticket.
     *
     * @return mixed
     */
    public function update(User $user, Ticket $ticket)
    {
        if (! $user->isFromPlatform()) {
            return false;
        }

        return (new Authorize($user, 'update_ticket', $ticket))->check();
    }

    /**
     * Determine whether the user can reply the Ticket.
     *
     * @return mixed
     */
    public function reply(User $user, Ticket $ticket)
    {
        return (new Authorize($user, 'reply_ticket', $ticket))->check();
    }

    /**
     * Determine whether the user can assign the Ticket.
     *
     * @return mixed
     */
    public function assign(User $user, Ticket $ticket)
    {
        if (! $user->isFromPlatform()) {
            return false;
        }

        return (new Authorize($user, 'assign_ticket', $ticket))->check();
    }
}
