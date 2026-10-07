<?php

namespace App\Services;

use App\Enums\PondOperationalStatus;
use App\Exceptions\CultivationCycleConflict;
use App\Models\Pond;
use Illuminate\Support\Facades\DB;

class PondLifecycleService
{
    /** @param array<string, mixed> $data */
    public function update(Pond $pond, array $data): Pond
    {
        return DB::transaction(function () use ($pond, $data): Pond {
            $locked = Pond::query()->whereKey($pond->id)->lockForUpdate()->firstOrFail();
            $open = $locked->cultivationCycles()->whereIn('status', ['PLANNED', 'ACTIVE'])->exists();
            if ($open && in_array($data['operational_status'] ?? null, [PondOperationalStatus::MAINTENANCE->value, PondOperationalStatus::INACTIVE->value], true)) {
                throw new CultivationCycleConflict('This pond cannot be made unavailable while cultivation is active.');
            }
            if ($open && isset($data['location_id']) && (int) $data['location_id'] !== $locked->location_id) {
                throw new CultivationCycleConflict('This pond location cannot change while cultivation is active.');
            }
            $locked->update($data);

            return $locked->refresh();
        });
    }
}
