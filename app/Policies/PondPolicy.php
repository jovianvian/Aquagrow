<?php

namespace App\Policies;

use App\Enums\FarmMembershipRole;
use App\Models\Pond;
use App\Models\User;

class PondPolicy
{
    public function view(User $user, Pond $pond): bool
    {
        return $user->farmMemberships()->where('farm_id', $pond->farm_id)->exists();
    }

    public function update(User $user, Pond $pond): bool
    {
        return $user->farmMemberships()->where('farm_id', $pond->farm_id)->where('role', FarmMembershipRole::FARM_OWNER)->exists();
    }
}
