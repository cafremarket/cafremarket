<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Dispute;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DisputePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view disputees.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_dispute'))->check();
    }

    /**
     * Determine whether the user can view the dispute.
     *
     * @return mixed
     */
    public function view(User $user, Dispute $dispute)
    {
        return (new Authorize($user, 'view_dispute', $dispute))->check();
    }

    /**
     * Determine whether the user can response the dispute.
     *
     * @return mixed
     */
    public function response(User $user, Dispute $dispute)
    {
        return (new Authorize($user, 'response_dispute', $dispute))->check();
    }

    /**
     * Determine whether the user can reply the Ticket.
     *
     * @return mixed
     */
    public function storeResponse(User $user, Dispute $dispute)
    {
        return (new Authorize($user, 'response_dispute', $dispute))->check();
    }
}
