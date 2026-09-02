<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Location> */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return ['farm_id' => Farm::factory(), 'name' => fake()->unique()->city().' Site', 'slug' => fake()->unique()->slug()];
    }
}
