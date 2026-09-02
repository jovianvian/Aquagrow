<?php

namespace App\Models;

use App\Enums\PondOperationalStatus;
use App\Enums\PondType;
use Database\Factories\PondFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pond extends Model
{
    /** @use HasFactory<PondFactory> */
    use HasFactory;

    protected $fillable = ['farm_id', 'location_id', 'name', 'code', 'type', 'operational_status', 'length_m', 'width_m', 'depth_m', 'water_volume_l', 'capacity_fish', 'notes'];

    protected function casts(): array
    {
        return ['type' => PondType::class, 'operational_status' => PondOperationalStatus::class];
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
}
