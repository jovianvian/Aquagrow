<?php

namespace Database\Factories;

use App\Enums\PondOperationalStatus;
use App\Enums\PondType;
use App\Models\Location;
use App\Models\Pond;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Pond> */
class PondFactory extends Factory
{
    protected $model = Pond::class;

    public function configure(): static
    {
        return $this->afterMaking(function (Pond $pond): void {
            $pond->farm_id = Location::query()->findOrFail($pond->location_id)->farm_id;
        });
    }

    public function definition(): array
    {
        $location = Location::factory();

        return ['farm_id' => null, 'location_id' => $location, 'name' => fake()->unique()->word().' Pond', 'code' => 'P-'.fake()->unique()->numerify('####'), 'type' => PondType::EARTHEN, 'operational_status' => PondOperationalStatus::AVAILABLE];
    }
}
