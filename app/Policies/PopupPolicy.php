<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Popup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PopupPolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return (new Authorize($user, 'customize_appearance'))->check();
    }

    public function view(User $user, Popup $popup)
    {
        return (new Authorize($user, 'customize_appearance', $popup))->check();
    }

    public function create(User $user)
    {
        return (new Authorize($user, 'customize_appearance'))->check();
    }

    public function update(User $user, Popup $popup)
    {
        return (new Authorize($user, 'customize_appearance', $popup))->check();
    }

    public function delete(User $user, Popup $popup)
    {
        return (new Authorize($user, 'customize_appearance', $popup))->check();
    }

    public function massDelete(User $user)
    {
        return (new Authorize($user, 'customize_appearance'))->check();
    }
}
