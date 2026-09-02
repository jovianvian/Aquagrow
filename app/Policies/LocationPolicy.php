<?php

namespace App\Policies;

use App\Enums\FarmMembershipRole;
use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function view(User $user, Location $location): bool
    {
        return $user->farmMemberships()->where('farm_id', $location->farm_id)->exists();
    }

    public function update(User $user, Location $location): bool
    {
        return $user->farmMemberships()->where('farm_id', $location->farm_id)->where('role', FarmMembershipRole::FARM_OWNER)->exists();
    }
}
