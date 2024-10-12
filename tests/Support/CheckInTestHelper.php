<?php


namespace Tests\Support;

use App\Models\User;
use App\Models\Location;

class CheckInTestHelper
{
    public static function createUser($attributes = [])
    {
        return User::factory()->create($attributes);
    }

    public static function createLocation($attributes = [])
    {
        return Location::factory()->create($attributes);
    }

    public static function checkInUser($user, $locationId)
    {
        return $user->withoutMiddleware()->post(route('checkin.process'), [
            'qr_code' => 'http://example.com/checkin/' . $locationId,
        ]);
    }

    public static function checkOutUser($user)
    {
        return $user->post('/checkout');
    }
}
