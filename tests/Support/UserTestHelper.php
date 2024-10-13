<?php
namespace Tests\Support;

use App\Models\User;

class UserTestHelper
{
    public static function createUser($attributes = [])
    {
        return User::factory()->create($attributes);
    }

    public static function createAdminUser($attributes = [])
    {
        return User::factory()->create(array_merge(['is_admin' => true], $attributes));
    }

    public static function createNonAdminUser($attributes = [])
    {
        return User::factory()->create(array_merge(['is_admin' => false], $attributes));
    }

    public static function updateUserRoleRequest($user, $targetUserId, $attributes)
    {
        return $user->put(route('users.update.role', $targetUserId), $attributes);
    }

    public static function updateUserProfileRequest($user, $attributes, $targetUserId = null)
    {
        $userId = $targetUserId ?? $user->id;
        return $user->put(route('profile.update', $userId), $attributes);
    }
}

