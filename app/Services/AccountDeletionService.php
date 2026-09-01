<?php

namespace App\Services;

use App\Enums\FarmMembershipRole;
use App\Exceptions\SoleOwnerAccountDeletion;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AccountDeletionService
{
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $farmIds = $user->farmMemberships()
                ->where('role', FarmMembershipRole::FARM_OWNER)
                ->pluck('farm_id')
                ->sort()
                ->values();

            $farms = Farm::query()
                ->whereIn('id', $farmIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($farms as $farm) {
                $ownerCount = $farm->members()
                    ->wherePivot('role', FarmMembershipRole::FARM_OWNER)
                    ->count();

                if ($ownerCount <= 1) {
                    throw new SoleOwnerAccountDeletion;
                }
            }

            $user->delete();
        });
    }
}
