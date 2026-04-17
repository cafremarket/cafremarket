<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Country;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CountryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view countrys.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return $user->isAdmin();
        // return (new Authorize($user, 'view_utility'))->check();
    }

    /**
     * Determine whether the user can view the Country.
     *
     * @return mixed
     */
    public function view(User $user, Country $country)
    {
        return $user->isAdmin();
        // return (new Authorize($user, 'view_utility', $country))->check();
    }

    /**
     * Determine whether the user can create Countrys.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->isAdmin();
        // return (new Authorize($user, 'add_utility'))->check();
    }

    /**
     * Determine whether the user can update the Country.
     *
     * @return mixed
     */
    public function update(User $user, Country $country)
    {
        return $user->isAdmin();
        // return (new Authorize($user, 'edit_utility', $country))->check();
    }

    /**
     * Determine whether the user can delete the Country.
     *
     * @return mixed
     */
    public function delete(User $user, Country $country)
    {
        return $user->isAdmin();
        // return (new Authorize($user, 'delete_utility', $country))->check();
    }

    /**
     * Determine whether the user can delete the Product.
     *
     * @return mixed
     */
    public function massDelete(User $user)
    {
        return $user->isAdmin();
        // return (new Authorize($user, 'delete_utility'))->check();
    }
}
