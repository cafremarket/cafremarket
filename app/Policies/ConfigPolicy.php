<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Config;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConfigPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view configs.
     *
     * @return mixed
     */
    public function view(User $user)
    {
        return (new Authorize($user, 'view_config'))->check();
    }

    /**
     * Determine whether the user can update the Config.
     *
     * @return mixed
     */
    public function update(User $user, Config $config)
    {
        return (new Authorize($user, 'edit_config', $config))->check();
    }
}
