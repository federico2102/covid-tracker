<?php

namespace Database\Factories;

use App\Models\CheckIn;
use App\Models\User;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CheckInFactory extends Factory
{
    protected $model = CheckIn::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Create a new user
            'location_id' => Location::factory(), // Create a new location
            'check_in_time' => Carbon::now(), // Set check-in time to the current time
            'check_out_time' => null, // Initially, the user is checked in, so no check-out time
        ];
    }
}
