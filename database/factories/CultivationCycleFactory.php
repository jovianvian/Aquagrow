<?php

namespace Database\Factories;

use App\Enums\CultivationCycleStatus;
use App\Models\CultivationCycle;
use App\Models\FishSpecies;
use App\Models\Pond;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CultivationCycle> */
class CultivationCycleFactory extends Factory
{
    protected $model = CultivationCycle::class;

    public function definition(): array
    {
        return ['farm_id' => null, 'pond_id' => Pond::factory(), 'fish_species_id' => FishSpecies::factory(), 'batch_code' => fake()->unique()->bothify('B-####'), 'status' => CultivationCycleStatus::PLANNED];
    }

    public function configure(): static
    {
        return $this->afterMaking(function ($cycle): void {
            $cycle->farm_id = Pond::query()->findOrFail($cycle->pond_id)->farm_id;
        });
    }
}
