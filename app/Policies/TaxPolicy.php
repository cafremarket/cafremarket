<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaxPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view taxes.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_tax'))->check();
    }

    /**
     * Determine whether the user can view the Tax.
     *
     * @return mixed
     */
    public function view(User $user, Tax $tax)
    {
        return (new Authorize($user, 'view_tax', $tax))->check();
    }

    /**
     * Determine whether the user can create Taxs.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return (new Authorize($user, 'add_tax'))->check();
    }

    /**
     * Determine whether the user can update the Tax.
     *
     * @return mixed
     */
    public function update(User $user, Tax $tax)
    {
        return (new Authorize($user, 'edit_tax', $tax))->check();
    }

    /**
     * Determine whether the user can delete the Tax.
     *
     * @return mixed
     */
    public function delete(User $user, Tax $tax)
    {
        return (new Authorize($user, 'delete_tax', $tax))->check();
    }

    /**
     * Determine whether the user can delete the Product.
     *
     * @return mixed
     */
    public function massDelete(User $user)
    {
        return (new Authorize($user, 'delete_tax'))->check();
    }
}
