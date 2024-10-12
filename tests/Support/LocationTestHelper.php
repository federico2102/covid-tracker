<?php

namespace Tests\Support;

use App\Models\Location;

class LocationTestHelper
{
    public static function createLocation($attributes = [])
    {
        return Location::factory()->create($attributes);
    }

    public static function createLocationRequest($user, $attributes)
    {
        return $user->post(route('locations.store'), $attributes);
    }

    public static function updateLocationRequest($user, $locationId, $attributes)
    {
        return $user->put(route('locations.update', $locationId), $attributes);
    }

    public static function deleteLocationRequest($user, $locationId)
    {
        return $user->delete(route('locations.destroy', $locationId));
    }
}
