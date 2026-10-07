<?php

namespace App\Policies;

use App\Enums\FarmMembershipRole;
use App\Models\CultivationCycle;
use App\Models\Farm;
use App\Models\User;
use App\Services\ActiveFarmContext;

class CultivationCyclePolicy
{
    public function view(User $user, CultivationCycle $cycle): bool
    {
        return $cycle->exists && $this->memberInActiveFarm($user, $cycle->farm_id);
    }

    public function create(User $user, Farm $farm): bool
    {
        return $user->farmMemberships()->where('farm_id', $farm->id)->where('role', FarmMembershipRole::FARM_OWNER->value)->exists();
    }

    public function update(User $user, CultivationCycle $cycle): bool
    {
        return $this->owner($user, $cycle->farm_id) && $this->activeFarmMatches($user, $cycle->farm_id) && $cycle->getRawOriginal('status') === 'PLANNED';
    }

    public function start(User $user, CultivationCycle $cycle): bool
    {
        return $this->owner($user, $cycle->farm_id) && $this->activeFarmMatches($user, $cycle->farm_id);
    }

    public function cancel(User $user, CultivationCycle $cycle): bool
    {
        return $this->owner($user, $cycle->farm_id) && $this->activeFarmMatches($user, $cycle->farm_id);
    }

    private function owner(User $user, ?int $farmId): bool
    {
        if ($farmId === null) {
            return false;
        }

        return $user->farmMemberships()->where('farm_id', $farmId)->where('role', FarmMembershipRole::FARM_OWNER->value)->exists();
    }

    private function memberInActiveFarm(User $user, ?int $farmId): bool
    {
        if ($farmId === null) {
            return false;
        }

        return $this->activeFarmMatches($user, $farmId) && $user->farms()->whereKey($farmId)->exists();
    }

    private function activeFarmMatches(User $user, ?int $farmId): bool
    {
        return $farmId !== null && app(ActiveFarmContext::class)->current($user)?->id === $farmId;
    }
}
