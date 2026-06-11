<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BossScenario extends Model
{
    protected $fillable = ['level_id', 'slug', 'title', 'scene'];

    protected $casts = [
        'scene' => 'array',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(UserBossAttempt::class);
    }
}
