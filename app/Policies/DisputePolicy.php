<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Dispute;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DisputePolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return (new Authorize($user, 'view_dispute'))->check();
    }

    public function view(User $user, Dispute $dispute)
    {
        return (new Authorize($user, 'view_dispute', $dispute))->check();
    }

    public function response(User $user, Dispute $dispute)
    {
        return (new Authorize($user, 'response_dispute', $dispute))->check();
    }

    public function storeResponse(User $user, Dispute $dispute)
    {
        return (new Authorize($user, 'response_dispute', $dispute))->check();
    }

    public function close(User $user, Dispute $dispute)
    {
        return $user->isFromPlatform()
            && (new Authorize($user, 'response_dispute', $dispute))->check();
    }

    public function create(User $user)
    {
        return (new Authorize($user, 'response_dispute'))->check()
            || (new Authorize($user, 'view_dispute'))->check();
    }
}
