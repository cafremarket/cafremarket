<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShippingRatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create shipping_rates.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return (new Authorize($user, 'add_shipping_rate'))->check();
    }

    /**
     * Determine whether the user can update the shipping_rate.
     *
     * @return mixed
     */
    public function update(User $user, ShippingRate $shipping_rate)
    {
        return (new Authorize($user, 'edit_shipping_rate', $shipping_rate))->check();
    }

    /**
     * Determine whether the user can delete the shipping_rate.
     *
     * @return mixed
     */
    public function delete(User $user, ShippingRate $shipping_rate)
    {
        return (new Authorize($user, 'delete_shipping_rate', $shipping_rate))->check();
    }
}
