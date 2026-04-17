<?php

namespace App\Policies;

use App\Models\AttributeValue;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttributeValuePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view attributeValues.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the attributeValue.
     *
     * @return mixed
     */
    public function view(User $user, AttributeValue $attributeValue)
    {
        return true;
    }

    /**
     * Determine whether the user can create attributeValues.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the attributeValue.
     *
     * @return mixed
     */
    public function update(User $user, AttributeValue $attributeValue)
    {
        return $user->isFromPlatform() || $user->shop_id == $attributeValue->shop_id;
    }

    /**
     * Determine whether the user can delete the attributeValue.
     *
     * @return mixed
     */
    public function delete(User $user, AttributeValue $attributeValue)
    {
        return $user->isFromPlatform() || $user->shop_id == $attributeValue->shop_id;
    }

    /**
     * Determine whether the user can delete the Product.
     *
     * @param  \App\Models\AttributeValue  $attributeValue
     * @return mixed
     */
    public function massDelete(User $user, ?AttributeValue $attribute = null)
    {
        if ($attribute && ! $user->isFromPlatform() && $attribute->shop_id != $user->merchantId()) {
            return false;
        }

        return $user->isFromPlatform();
    }
}
