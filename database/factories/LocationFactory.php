<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'address' => $this->faker->address(),
            'geolocation' => $this->faker->latitude() . ',' . $this->faker->longitude(),
            'max_capacity' => $this->faker->numberBetween(10, 100),
            'current_people' => 0,
            'optional_details' => $this->faker->sentence(),
            'picture' => null, // or provide a default image path
            'qr_code' => null, // if you are generating this later
        ];
    }
}
