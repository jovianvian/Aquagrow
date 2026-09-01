<?php

namespace App\Services;

use App\Enums\FarmMembershipRole;
use App\Models\Farm;
use App\Models\FarmMembership;
use Illuminate\Support\Facades\DB;

final class FarmMembershipService
{
    public function changeRole(FarmMembership $membership, FarmMembershipRole $role): FarmMembership
    {
        return DB::transaction(function () use ($membership, $role): FarmMembership {
            $membership = FarmMembership::query()->whereKey($membership->id)->firstOrFail();
            Farm::query()->whereKey($membership->farm_id)->lockForUpdate()->firstOrFail();
            $membership = FarmMembership::query()->whereKey($membership->id)->lockForUpdate()->firstOrFail();
            if ($membership->getRawOriginal('role') === FarmMembershipRole::FARM_OWNER->value && $role !== FarmMembershipRole::FARM_OWNER
                && FarmMembership::query()->where('farm_id', $membership->farm_id)->where('role', FarmMembershipRole::FARM_OWNER)->count() <= 1) {
                throw new \DomainException('A farm must retain at least one owner.');
            }
            $membership->update(['role' => $role]);

            return $membership->refresh();
        });
    }

    public function remove(FarmMembership $membership): void
    {
        DB::transaction(function () use ($membership): void {
            $membership = FarmMembership::query()->whereKey($membership->id)->firstOrFail();
            Farm::query()->whereKey($membership->farm_id)->lockForUpdate()->firstOrFail();
            $membership = FarmMembership::query()->whereKey($membership->id)->lockForUpdate()->firstOrFail();
            if ($membership->getRawOriginal('role') === FarmMembershipRole::FARM_OWNER->value
                && FarmMembership::query()->where('farm_id', $membership->farm_id)->where('role', FarmMembershipRole::FARM_OWNER)->count() <= 1) {
                throw new \DomainException('A farm must retain at least one owner.');
            }
            $membership->delete();
        });
    }
}
