<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view products.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_product'))->check();
    }

    /**
     * Determine whether the user can view the Product.
     *
     * @return mixed
     */
    public function view(User $user, Product $product)
    {
        return true; // Everyone can view products
        // return (new Authorize($user, 'view_product', $product))->check();
    }

    /**
     * Determine whether the user can create Products.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return (new Authorize($user, 'add_product'))->check();
    }

    /**
     * Determine whether the user can update the Product.
     *
     * @return mixed
     */
    public function update(User $user, Product $product)
    {
        if ($user->isFromMerchant() && is_catalog_enabled()) {
            if (can_use_own_catalog_only()) {
                return $product->shop_id == $user->merchantId();
            }

            return $product->inventories_count == 0 && $product->shop_id == $user->merchantId();
        }

        return (new Authorize($user, 'edit_product', $product))->check();
    }

    /**
     * Determine whether the user can delete the Product.
     *
     * @return mixed
     */
    public function delete(User $user, Product $product)
    {
        if ($user->isFromMerchant()) {
            return $product->shop_id == $user->merchantId();
        }

        return (new Authorize($user, 'delete_product', $product))->check();
    }

    /**
     * Determine whether the user can delete the Product.
     *
     * @return mixed
     */
    public function massDelete(User $user)
    {
        return (new Authorize($user, 'delete_product'))->check();
    }
}
