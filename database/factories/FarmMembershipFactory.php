<?php

namespace Database\Factories;

use App\Enums\FarmMembershipRole;
use App\Models\FarmMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FarmMembership> */
class FarmMembershipFactory extends Factory
{
    protected $model = FarmMembership::class;

    public function definition(): array
    {
        return ['farm_id' => null, 'user_id' => null, 'role' => FarmMembershipRole::POND_OPERATOR];
    }
}
