<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\CategorySubGroup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategorySubGroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view category_sub_groups.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_category_sub_group'))->check();
    }

    /**
     * Determine whether the user can view the CategorySubGroup.
     *
     * @return mixed
     */
    public function view(User $user, CategorySubGroup $categorySubGroup)
    {
        return (new Authorize($user, 'view_category_sub_group', $categorySubGroup))->check();
    }

    /**
     * Determine whether the user can create CategorySubGroups.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return (new Authorize($user, 'add_category_sub_group'))->check();
    }

    /**
     * Determine whether the user can update the CategorySubGroup.
     *
     * @return mixed
     */
    public function update(User $user, CategorySubGroup $categorySubGroup)
    {
        return (new Authorize($user, 'edit_category_sub_group', $categorySubGroup))->check();
    }

    /**
     * Determine whether the user can delete the CategorySubGroup.
     *
     * @return mixed
     */
    public function delete(User $user, CategorySubGroup $categorySubGroup)
    {
        return (new Authorize($user, 'delete_category_sub_group', $categorySubGroup))->check();
    }

    /**
     * Determine whether the user can delete the Product.
     *
     * @return mixed
     */
    public function massDelete(User $user)
    {
        return (new Authorize($user, 'delete_category_sub_group'))->check();
    }
}
