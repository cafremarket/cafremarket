<?php

namespace App\Policies;

use App\Helpers\Authorize;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmailTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view email_templates.
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_utility'))->check();
    }

    /**
     * Determine whether the user can view the EmailTemplate.
     *
     * @return mixed
     */
    public function view(User $user, EmailTemplate $emailTemplate)
    {
        return (new Authorize($user, 'view_utility', $emailTemplate))->check();
    }

    /**
     * Determine whether the user can create EmailTemplates.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return (new Authorize($user, 'add_utility'))->check();
    }

    /**
     * Determine whether the user can update the EmailTemplate.
     *
     * @return mixed
     */
    public function update(User $user, EmailTemplate $emailTemplate)
    {
        return (new Authorize($user, 'edit_utility', $emailTemplate))->check();
    }

    /**
     * Determine whether the user can delete the EmailTemplate.
     *
     * @return mixed
     */
    public function delete(User $user, EmailTemplate $emailTemplate)
    {
        return (new Authorize($user, 'delete_utility', $emailTemplate))->check();
    }

    /**
     * Determine whether the user can delete the Product.
     *
     * @return mixed
     */
    public function massDelete(User $user)
    {
        return (new Authorize($user, 'delete_utility'))->check();
    }
}
