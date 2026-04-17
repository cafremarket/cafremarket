<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view roles.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_role'))->check();
    }

    /**
     * Determine whether the user can view the Role.
     *
     * @return mixed
     */
    public function view(User $user, Role $role)
    {
        return (new Authorize($user, 'view_role', $role))->check();
    }

    /**
     * Determine whether the user can create Roles.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return (new Authorize($user, 'add_role'))->check();
    }

    /**
     * Determine whether the user can update the Role.
     *
     * @return mixed
     */
    public function update(User $user, Role $role)
    {
        return (new Authorize($user, 'edit_role', $role))->check();
    }

    /**
     * Determine whether the user can delete the Role.
     *
     * @return mixed
     */
    public function delete(User $user, Role $role)
    {
        return (new Authorize($user, 'delete_role', $role))->check();
    }

    /**
     * Determine whether the user can delete the Product.
     *
     * @return mixed
     */
    public function massDelete(User $user)
    {
        return (new Authorize($user, 'delete_role'))->check();
    }
}
