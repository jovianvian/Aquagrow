<?php

namespace App\Models;

use Database\Factories\FarmFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    /** @use HasFactory<FarmFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'timezone', 'currency'];

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'farm_memberships')->withPivot('role')->withTimestamps();
    }

    /** @return HasMany<Location, $this> */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /** @return HasMany<Pond, $this> */
    public function ponds(): HasMany
    {
        return $this->hasMany(Pond::class);
    }
}
