<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewPolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return (new Authorize($user, 'view_review'))->check();
    }

    public function view(User $user, Review $review)
    {
        return (new Authorize($user, 'view_review', $review))->check();
    }

    public function reply(User $user, Review $review)
    {
        return (new Authorize($user, 'reply_review', $review))->check();
    }

    public function requestDelete(User $user, Review $review)
    {
        return (new Authorize($user, 'request_delete_review', $review))->check();
    }

    /**
     * Direct hard-delete of a review - admin-only (Review Moderation module,
     * access=Platform, never granted to the Merchant role).
     */
    public function delete(User $user, Review $review)
    {
        return (new Authorize($user, 'delete_review_moderation', $review))->check();
    }
}
