<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PermissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the Permission.
     *
     * @return mixed
     */
    public function view(User $user, Permission $Permission)
    {
        return false;
    }

    /**
     * Determine whether the user can create Permissions.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return false;
    }

    /**
     * Determine whether the user can update the Permission.
     *
     * @return mixed
     */
    public function update(User $user, Permission $Permission)
    {
        return false;
    }

    /**
     * Determine whether the user can delete the Permission.
     *
     * @return mixed
     */
    public function delete(User $user, Permission $Permission)
    {
        return false;
    }
}
