<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view suppliers.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_supplier'))->check();
    }

    /**
     * Determine whether the user can view the Supplier.
     *
     * @return mixed
     */
    public function view(User $user, Supplier $supplier)
    {
        return (new Authorize($user, 'view_supplier', $supplier))->check();
    }

    /**
     * Determine whether the user can create Suppliers.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return (new Authorize($user, 'add_supplier'))->check();
    }

    /**
     * Determine whether the user can update the Supplier.
     *
     * @return mixed
     */
    public function update(User $user, Supplier $supplier)
    {
        return (new Authorize($user, 'edit_supplier', $supplier))->check();
    }

    /**
     * Determine whether the user can delete the Supplier.
     *
     * @return mixed
     */
    public function delete(User $user, Supplier $supplier)
    {
        return (new Authorize($user, 'delete_supplier', $supplier))->check();
    }

    /**
     * Determine whether the user can delete the Product.
     *
     * @return mixed
     */
    public function massDelete(User $user)
    {
        return (new Authorize($user, 'delete_supplier'))->check();
    }
}
