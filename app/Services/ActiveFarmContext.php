<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\User;

final class ActiveFarmContext
{
    private const KEY = 'active_farm_id';

    public function current(User $user): ?Farm
    {
        $id = session(self::KEY);
        $farm = $id ? $user->farms()->whereKey($id)->first() : null;
        if (! $farm) {
            $farms = $user->farms()->orderBy('farms.id')->limit(2)->get();
            $farm = $farms->count() === 1 ? $farms->first() : null;
            $farm ? session()->put(self::KEY, $farm->id) : session()->forget(self::KEY);
        }

        return $farm;
    }

    public function select(User $user, Farm $farm): bool
    {
        if (! $user->farms()->whereKey($farm->id)->exists()) {
            return false;
        } session()->put(self::KEY, $farm->id);

        return true;
    }

    public function forget(): void
    {
        session()->forget(self::KEY);
    }
}
