<?php

namespace App\Models;

use App\Enums\CultivationCycleStatus;
use Database\Factories\CultivationCycleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CultivationCycle extends Model
{
    /** @use HasFactory<CultivationCycleFactory> */
    use HasFactory;

    protected $fillable = [
        'farm_id', 'pond_id', 'fish_species_id', 'batch_code', 'status',
        'planned_start_date', 'stocked_at', 'initial_fish_count',
        'initial_average_weight_g', 'target_average_weight_g',
        'expected_harvest_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => CultivationCycleStatus::class,
            'planned_start_date' => 'immutable_date',
            'stocked_at' => 'immutable_datetime',
            'expected_harvest_date' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<Farm, $this> */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /** @return BelongsTo<Pond, $this> */
    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    /** @return BelongsTo<FishSpecies, $this> */
    public function fishSpecies(): BelongsTo
    {
        return $this->belongsTo(FishSpecies::class);
    }
}
