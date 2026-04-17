<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CartPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view carts.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_cart'))->check();
    }

    /**
     * Determine whether the user can view the Cart.
     *
     * @return mixed
     */
    public function view(User $user, Cart $cart)
    {
        return (new Authorize($user, 'view_cart', $cart))->check();
    }

    /**
     * Determine whether the user can create Carts.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->isFromMerchant() ? (new Authorize($user, 'add_cart'))->check() : false;
    }

    /**
     * Determine whether the user can update the Cart.
     *
     * @return mixed
     */
    public function update(User $user, Cart $cart)
    {
        return $user->isFromMerchant() ? (new Authorize($user, 'edit_cart'))->check() : false;
    }

    /**
     * Determine whether the user can delete the Cart.
     *
     * @return mixed
     */
    public function delete(User $user, Cart $cart)
    {
        return (new Authorize($user, 'delete_cart', $cart))->check();
    }

    /**
     * Determine whether the user can delete the Product.
     *
     * @return mixed
     */
    public function massDelete(User $user)
    {
        return (new Authorize($user, 'delete_cart'))->check();
    }
}
