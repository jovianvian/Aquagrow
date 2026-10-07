<?php

namespace App\Models;

use Database\Factories\FishSpeciesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FishSpecies extends Model
{
    /** @use HasFactory<FishSpeciesFactory> */
    use HasFactory;

    protected $fillable = ['code', 'common_name', 'scientific_name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<CultivationCycle, $this> */
    public function cultivationCycles(): HasMany
    {
        return $this->hasMany(CultivationCycle::class);
    }
}
