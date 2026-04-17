<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RefundPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  string  $ability
     * @return void|bool
     */
    public function before(User $user, $ability)
    {
        if (! $user->canManageOrderPayments()) {
            return false;
        }
    }

    /**
     * Determine whether the user can view refunds.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_refund'))->check();
    }

    /**
     * Determine whether the user can view the Refund.
     *
     * @return mixed
     */
    public function view(User $user, Refund $refund)
    {
        return (new Authorize($user, 'view_refund', $refund))->check();
    }

    /**
     * Determine whether the user can create Refunds.
     *
     * @return mixed
     */
    public function form(User $user)
    {
        return (new Authorize($user, 'initiate_refund'))->check();
    }

    /**
     * Determine whether the user can create Refunds.
     *
     * @return mixed
     */
    public function initiate(User $user)
    {
        return (new Authorize($user, 'initiate_refund'))->check();
    }

    /**
     * Determine whether the user can approve the Refund.
     *
     * @return mixed
     */
    public function approve(User $user, Refund $refund)
    {
        return (new Authorize($user, 'approve_refund', $refund))->check();
    }

    /**
     * Determine whether the user can decline the Refund.
     *
     * @return mixed
     */
    public function decline(User $user, Refund $refund)
    {
        return (new Authorize($user, 'approve_refund', $refund))->check();
    }
}
