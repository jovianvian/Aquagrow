<?php

namespace App\Services;

use App\Enums\CultivationCycleStatus;
use App\Enums\PondOperationalStatus;
use App\Exceptions\CultivationCycleConflict;
use App\Models\CultivationCycle;
use App\Models\Farm;
use App\Models\Pond;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CultivationCycleService
{
    public function __construct(private CultivationCycleDatabaseConflictMapper $conflicts) {}

    /** @param array<string, mixed> $data */
    public function createPlanned(User $user, Farm $farm, array $data): CultivationCycle
    {
        try {
            return DB::transaction(function () use ($farm, $data): CultivationCycle {
                $pond = Pond::query()->whereKey($data['pond_id'])->where('farm_id', $farm->id)->lockForUpdate()->firstOrFail();
                if ($pond->getRawOriginal('operational_status') === PondOperationalStatus::INACTIVE->value || $pond->cultivationCycles()->whereIn('status', ['PLANNED', 'ACTIVE'])->exists()) {
                    throw new CultivationCycleConflict('This pond already has an open cycle or is inactive.');
                }

                return CultivationCycle::query()->create(['farm_id' => $farm->id, 'pond_id' => $pond->id, 'fish_species_id' => $data['fish_species_id'], 'batch_code' => strtoupper($data['batch_code']), 'status' => CultivationCycleStatus::PLANNED, 'planned_start_date' => $data['planned_start_date'] ?? null, 'target_average_weight_g' => $data['target_average_weight_g'] ?? null, 'expected_harvest_date' => $data['expected_harvest_date'] ?? null, 'notes' => $data['notes'] ?? null]);
            });
        } catch (QueryException $exception) {
            throw $this->conflicts->map($exception) ?? $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public function start(CultivationCycle $cycle, array $data): CultivationCycle
    {
        try {
            return DB::transaction(function () use ($cycle, $data): CultivationCycle {
                $pond = Pond::query()->whereKey($cycle->pond_id)->where('farm_id', $cycle->farm_id)->lockForUpdate()->firstOrFail();
                $cycle = CultivationCycle::query()->whereKey($cycle->id)->lockForUpdate()->firstOrFail();
                if ($cycle->getRawOriginal('status') !== CultivationCycleStatus::PLANNED->value || $pond->getRawOriginal('operational_status') !== PondOperationalStatus::AVAILABLE->value || ! $cycle->fishSpecies()->where('is_active', true)->exists()) {
                    throw new CultivationCycleConflict('This cycle cannot be started in its current state.');
                }
                $farm = Farm::query()->findOrFail($cycle->farm_id);
                $stockedAt = CarbonImmutable::parse($data['stocked_at'], $farm->timezone)->utc();
                if ($stockedAt->isFuture()) {
                    throw new CultivationCycleConflict('Stocked time cannot be in the future.');
                }
                if ($cycle->getRawOriginal('expected_harvest_date') && $cycle->getRawOriginal('expected_harvest_date') < $stockedAt->toDateString()) {
                    throw new CultivationCycleConflict('Expected harvest date cannot be before stocking.');
                }
                if ($cycle->target_average_weight_g !== null && (float) $cycle->target_average_weight_g <= (float) $data['initial_average_weight_g']) {
                    throw new CultivationCycleConflict('Target weight must exceed initial weight.');
                }
                $cycle->forceFill(['status' => CultivationCycleStatus::ACTIVE, 'stocked_at' => $stockedAt, 'initial_fish_count' => $data['initial_fish_count'], 'initial_average_weight_g' => $data['initial_average_weight_g']])->save();

                return $cycle->refresh();
            });
        } catch (QueryException $exception) {
            throw $this->conflicts->map($exception) ?? $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public function updatePlanned(CultivationCycle $cycle, array $data): CultivationCycle
    {
        try {
            return DB::transaction(function () use ($cycle, $data): CultivationCycle {
                Pond::query()->whereKey($cycle->pond_id)->where('farm_id', $cycle->farm_id)->lockForUpdate()->firstOrFail();
                $locked = CultivationCycle::query()->whereKey($cycle->id)->lockForUpdate()->firstOrFail();
                if ($locked->getRawOriginal('status') !== CultivationCycleStatus::PLANNED->value) {
                    throw new CultivationCycleConflict('Only planned cycles can be edited.');
                }
                $locked->update(['fish_species_id' => $data['fish_species_id'], 'batch_code' => strtoupper($data['batch_code']), 'planned_start_date' => $data['planned_start_date'] ?? null, 'target_average_weight_g' => $data['target_average_weight_g'] ?? null, 'expected_harvest_date' => $data['expected_harvest_date'] ?? null, 'notes' => $data['notes'] ?? null]);

                return $locked->refresh();
            });
        } catch (QueryException $exception) {
            throw $this->conflicts->map($exception) ?? $exception;
        }
    }

    public function cancel(CultivationCycle $cycle): void
    {
        DB::transaction(function () use ($cycle): void {
            Pond::query()->whereKey($cycle->pond_id)->where('farm_id', $cycle->farm_id)->lockForUpdate()->firstOrFail();
            $locked = CultivationCycle::query()->whereKey($cycle->id)->lockForUpdate()->firstOrFail();
            if ($locked->getRawOriginal('status') !== CultivationCycleStatus::PLANNED->value) {
                throw new CultivationCycleConflict('Only planned cycles can be cancelled.');
            }
            $locked->update(['status' => CultivationCycleStatus::CANCELLED]);
        });
    }
}
