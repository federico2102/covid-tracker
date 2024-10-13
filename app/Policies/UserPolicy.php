<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function updateRole(User $authUser, User $targetUser)
    {
        // Only admins can update roles
        return $authUser->is_admin;
    }

    public function updateProfile(User $authUser, User $targetUser): bool
    {
        return $authUser->id === $targetUser->id;
    }
}

