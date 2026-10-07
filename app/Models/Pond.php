<?php

namespace App\Models;

use App\Enums\CultivationCycleStatus;
use App\Enums\PondOperationalStatus;
use App\Enums\PondType;
use Database\Factories\PondFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pond extends Model
{
    /** @use HasFactory<PondFactory> */
    use HasFactory;

    protected $fillable = ['farm_id', 'location_id', 'name', 'code', 'type', 'operational_status', 'length_m', 'width_m', 'depth_m', 'water_volume_l', 'capacity_fish', 'notes'];

    protected function casts(): array
    {
        return ['type' => PondType::class, 'operational_status' => PondOperationalStatus::class];
    }

    public function derivedLifecycle(): string
    {
        if ($this->getRawOriginal('operational_status') === PondOperationalStatus::MAINTENANCE->value) {
            return 'MAINTENANCE';
        }
        if ($this->getRawOriginal('operational_status') === PondOperationalStatus::INACTIVE->value) {
            return 'INACTIVE';
        }
        $open = $this->relationLoaded('cultivationCycles')
            ? $this->cultivationCycles->first(fn ($cycle) => in_array($cycle->getRawOriginal('status'), ['PLANNED', 'ACTIVE'], true))
            : $this->cultivationCycles()->whereIn('status', ['PLANNED', 'ACTIVE'])->first();

        return $open?->getRawOriginal('status') === CultivationCycleStatus::ACTIVE->value ? 'ACTIVE_CULTIVATION' : ($open ? 'CYCLE_PLANNED' : 'EMPTY_AVAILABLE');
    }

    /** @return BelongsTo<Farm, $this> */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return HasMany<CultivationCycle, $this> */
    public function cultivationCycles(): HasMany
    {
        return $this->hasMany(CultivationCycle::class);
    }
}
