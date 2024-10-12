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
}
