<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\DeliveryBoy;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeliveryBoyPolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return (new Authorize($user, 'view_delivery_boy'))->check();
    }

    public function view(User $user, DeliveryBoy $deliveryBoy)
    {
        return (new Authorize($user, 'view_delivery_boy', $deliveryBoy))->check();
    }

    public function create(User $user)
    {
        return (new Authorize($user, 'add_delivery_boy'))->check();
    }

    public function update(User $user, DeliveryBoy $deliveryBoy)
    {
        return (new Authorize($user, 'edit_delivery_boy', $deliveryBoy))->check();
    }

    public function delete(User $user, DeliveryBoy $deliveryBoy)
    {
        return (new Authorize($user, 'delete_delivery_boy', $deliveryBoy))->check();
    }

    public function massDelete(User $user)
    {
        return (new Authorize($user, 'delete_delivery_boy'))->check();
    }
}
