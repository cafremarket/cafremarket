<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Categories are fully platform/admin-managed — stores never own or
 * manage them, so this policy has no per-shop ownership branch.
 */
class CategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view categories.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return $user->isFromPlatform()
            && (new Authorize($user, 'view_category'))->check();
    }

    /**
     * Determine whether the user can view the Category.
     *
     * @return mixed
     */
    public function view(User $user, Category $category)
    {
        return $user->isFromPlatform()
            && (new Authorize($user, 'view_category', $category))->check();
    }

    /**
     * Determine whether the user can create Categories.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->isFromPlatform()
            && (new Authorize($user, 'add_category'))->check();
    }

    /**
     * Determine whether the user can update the Category.
     *
     * @return mixed
     */
    public function update(User $user, Category $category)
    {
        return $user->isFromPlatform()
            && (new Authorize($user, 'edit_category', $category))->check();
    }

    /**
     * Determine whether the user can delete the Category.
     *
     * @return mixed
     */
    public function delete(User $user, Category $category)
    {
        return $user->isFromPlatform()
            && (new Authorize($user, 'delete_category', $category))->check();
    }

    /**
     * Determine whether the user can mass-delete Categories.
     *
     * @return mixed
     */
    public function massDelete(User $user)
    {
        return $user->isFromPlatform()
            && (new Authorize($user, 'delete_category'))->check();
    }
}
