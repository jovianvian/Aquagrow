<?php

namespace Database\Factories;

use App\Models\FishSpecies;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FishSpecies> */
class FishSpeciesFactory extends Factory
{
    protected $model = FishSpecies::class;

    public function definition(): array
    {
        return ['code' => fake()->unique()->lexify('SPEC??'), 'common_name' => fake()->word(), 'is_active' => true];
    }
}
