<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CurrencyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view currencys.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return $user->isAdmin();
        // return (new Authorize($user, 'view_utility'))->check();
    }

    /**
     * Determine whether the user can view the Currency.
     *
     * @return mixed
     */
    public function view(User $user, Currency $currency)
    {
        return $user->isAdmin();
        // return (new Authorize($user, 'view_utility', $currency))->check();
    }

    /**
     * Determine whether the user can create Currencys.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->isAdmin();
        // return (new Authorize($user, 'add_utility'))->check();
    }

    /**
     * Determine whether the user can update the Currency.
     *
     * @return mixed
     */
    public function update(User $user, Currency $currency)
    {
        return $user->isAdmin();
        // return (new Authorize($user, 'edit_utility', $currency))->check();
    }

    /**
     * Determine whether the user can delete the Currency.
     *
     * @return mixed
     */
    public function delete(User $user, Currency $currency)
    {
        return $user->isAdmin();
        // return (new Authorize($user, 'delete_utility', $currency))->check();
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
