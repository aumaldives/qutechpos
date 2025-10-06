<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Auth\Access\HandlesAuthorization;

class WebhookPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any webhooks.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('webhook.view');
    }

    /**
     * Determine whether the user can view the webhook.
     */
    public function view(User $user, Webhook $webhook): bool
    {
        return $user->can('webhook.view') && 
               $user->business_id == $webhook->business_id;
    }

    /**
     * Determine whether the user can create webhooks.
     */
    public function create(User $user): bool
    {
        return $user->can('webhook.create');
    }

    /**
     * Determine whether the user can update the webhook.
     */
    public function update(User $user, Webhook $webhook): bool
    {
        return $user->can('webhook.update') && 
               $user->business_id == $webhook->business_id;
    }

    /**
     * Determine whether the user can delete the webhook.
     */
    public function delete(User $user, Webhook $webhook): bool
    {
        return $user->can('webhook.delete') && 
               $user->business_id == $webhook->business_id;
    }
}