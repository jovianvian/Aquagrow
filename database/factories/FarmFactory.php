<?php

namespace Database\Factories;

use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Farm> */
class FarmFactory extends Factory
{
    protected $model = Farm::class;

    public function definition(): array
    {
        $name = fake()->unique()->company().' Farm';

        return ['name' => $name, 'slug' => fake()->unique()->slug(2), 'timezone' => 'Asia/Jakarta', 'currency' => 'IDR'];
    }
}
