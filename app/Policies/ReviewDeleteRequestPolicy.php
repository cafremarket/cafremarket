<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\ReviewDeleteRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewDeleteRequestPolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return (new Authorize($user, 'view_review_moderation'))->check();
    }

    public function view(User $user, ReviewDeleteRequest $deleteRequest)
    {
        return (new Authorize($user, 'view_review_moderation', $deleteRequest))->check();
    }

    public function approve(User $user, ReviewDeleteRequest $deleteRequest)
    {
        return (new Authorize($user, 'approve_review_moderation', $deleteRequest))->check();
    }

    public function reject(User $user, ReviewDeleteRequest $deleteRequest)
    {
        return (new Authorize($user, 'reject_review_moderation', $deleteRequest))->check();
    }
}
