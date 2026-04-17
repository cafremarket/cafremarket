<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\GiftCard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GiftCardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view gift_cards.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_gift_card'))->check();
    }

    /**
     * Determine whether the user can view the GiftCard.
     *
     * @return mixed
     */
    public function view(User $user, GiftCard $giftCard)
    {
        return (new Authorize($user, 'view_gift_card', $giftCard))->check();
    }

    /**
     * Determine whether the user can create GiftCards.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return (new Authorize($user, 'add_gift_card'))->check();
    }

    /**
     * Determine whether the user can update the GiftCard.
     *
     * @return mixed
     */
    public function update(User $user, GiftCard $giftCard)
    {
        return (new Authorize($user, 'edit_gift_card', $giftCard))->check();
    }

    /**
     * Determine whether the user can delete the GiftCard.
     *
     * @return mixed
     */
    public function delete(User $user, GiftCard $giftCard)
    {
        return (new Authorize($user, 'delete_gift_card', $giftCard))->check();
    }
}
