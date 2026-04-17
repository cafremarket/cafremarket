<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShopPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view shops.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_vendor'))->check();
    }

    /**
     * Determine whether the user can view the Shop.
     *
     * @return mixed
     */
    public function view(User $user, Shop $shop)
    {
        return (new Authorize($user, 'view_vendor', $shop))->check();
    }

    /**
     * Determine whether the user can create Shops.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return (new Authorize($user, 'add_vendor'))->check();
    }

    /**
     * Determine whether the user can update the Shop.
     *
     * @return mixed
     */
    public function update(User $user, Shop $shop)
    {
        return (new Authorize($user, 'edit_vendor', $shop))->check();
    }

    /**
     * Determine whether the user can delete the Shop.
     *
     * @return mixed
     */
    public function delete(User $user, Shop $shop)
    {
        return (new Authorize($user, 'delete_vendor', $shop))->check();
    }

    /**
     * Determine whether the user can delete the Product.
     *
     * @return mixed
     */
    public function massDelete(User $user)
    {
        return (new Authorize($user, 'delete_vendor'))->check();
    }
}
