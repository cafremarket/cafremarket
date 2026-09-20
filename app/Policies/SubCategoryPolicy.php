<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * SubCategories share the "Category" module's permission slugs — category
 * and sub-category management is one admin capability, not two.
 */
class SubCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view sub-categories.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return $user->isFromPlatform()
            && (new Authorize($user, 'view_category'))->check();
    }

    /**
     * Determine whether the user can view the SubCategory.
     *
     * @return mixed
     */
    public function view(User $user, SubCategory $subCategory)
    {
        return $user->isFromPlatform()
            && (new Authorize($user, 'view_category', $subCategory))->check();
    }

    /**
     * Determine whether the user can create SubCategories.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->isFromPlatform()
            && (new Authorize($user, 'add_category'))->check();
    }

    /**
     * Determine whether the user can update the SubCategory.
     *
     * @return mixed
     */
    public function update(User $user, SubCategory $subCategory)
    {
        return $user->isFromPlatform()
            && (new Authorize($user, 'edit_category', $subCategory))->check();
    }

    /**
     * Determine whether the user can delete the SubCategory.
     *
     * @return mixed
     */
    public function delete(User $user, SubCategory $subCategory)
    {
        return $user->isFromPlatform()
            && (new Authorize($user, 'delete_category', $subCategory))->check();
    }

    /**
     * Determine whether the user can mass-delete SubCategories.
     *
     * @return mixed
     */
    public function massDelete(User $user)
    {
        return $user->isFromPlatform()
            && (new Authorize($user, 'delete_category'))->check();
    }
}
