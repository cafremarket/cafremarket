<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\CategoryGroup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryGroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view category_groups.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_category_group'))->check();
    }

    /**
     * Determine whether the user can view the CategoryGroup.
     *
     * @return mixed
     */
    public function view(User $user, CategoryGroup $categoryGroup)
    {
        return (new Authorize($user, 'view_category_group', $categoryGroup))->check();
    }

    /**
     * Determine whether the user can create CategoryGroups.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return (new Authorize($user, 'add_category_group'))->check();
    }

    /**
     * Determine whether the user can update the CategoryGroup.
     *
     * @return mixed
     */
    public function update(User $user, CategoryGroup $categoryGroup)
    {
        return (new Authorize($user, 'edit_category_group', $categoryGroup))->check();
    }

    /**
     * Determine whether the user can delete the CategoryGroup.
     *
     * @return mixed
     */
    public function delete(User $user, CategoryGroup $categoryGroup)
    {
        return (new Authorize($user, 'delete_category_group', $categoryGroup))->check();
    }

    /**
     * Determine whether the user can delete the Product.
     *
     * @return mixed
     */
    public function massDelete(User $user)
    {
        return (new Authorize($user, 'delete_category_group'))->check();
    }
}
