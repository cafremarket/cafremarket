<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view users.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_user'))->check();
    }

    /**
     * Determine whether the user can view the User.
     *
     * @return mixed
     */
    public function view(User $user, User $model)
    {
        return (new Authorize($user, 'view_user', $model))->check();
    }

    /**
     * Determine whether the user can create Users.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return (new Authorize($user, 'add_user'))->check();
    }

    /**
     * Determine whether the user can update the User.
     *
     * @return mixed
     */
    public function update(User $user, User $model)
    {
        return (new Authorize($user, 'edit_user', $model))->check();
    }

    /**
     * Determine whether the user can delete the User.
     *
     * @return mixed
     */
    public function delete(User $user, User $model)
    {
        return (new Authorize($user, 'delete_user', $model))->check();
    }

    /**
     * Determine whether the user can delete the Product.
     *
     * @return mixed
     */
    public function massDelete(User $user)
    {
        return (new Authorize($user, 'delete_user'))->check();
    }

    /**
     * Determine whether the user can secretly login as user.
     *
     * @return mixed
     */
    public function secretLogin(User $user, User $model)
    {
        return (new Authorize($user, 'login_user', $model))->check();
    }
}
