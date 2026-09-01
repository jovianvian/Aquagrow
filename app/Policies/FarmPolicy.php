<?php

namespace App\Policies;

use App\Enums\FarmMembershipRole;
use App\Models\Farm;
use App\Models\User;

class FarmPolicy
{
    public function view(User $user, Farm $farm): bool
    {
        return $user->farms()->whereKey($farm->id)->exists();
    }

    public function update(User $user, Farm $farm): bool
    {
        return $user->farmMemberships()->where('farm_id', $farm->id)->where('role', FarmMembershipRole::FARM_OWNER)->exists();
    }

    public function viewMemberships(User $user, Farm $farm): bool
    {
        return $this->update($user, $farm);
    }
}
