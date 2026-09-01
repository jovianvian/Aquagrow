<?php

namespace App\Models;

use App\Enums\FarmMembershipRole;
use Database\Factories\FarmMembershipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmMembership extends Model
{
    /** @use HasFactory<FarmMembershipFactory> */
    use HasFactory;

    protected $fillable = ['farm_id', 'user_id', 'role'];

    protected function casts(): array
    {
        return ['role' => FarmMembershipRole::class];
    }

    /** @return BelongsTo<Farm, $this> */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
