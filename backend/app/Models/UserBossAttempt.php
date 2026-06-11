<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBossAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'boss_scenario_id',
        'stars',
        'hearts_remaining',
        'transcript',
        'completed_at',
    ];

    protected $casts = [
        'transcript' => 'array',
        'completed_at' => 'datetime',
        'stars' => 'integer',
        'hearts_remaining' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bossScenario(): BelongsTo
    {
        return $this->belongsTo(BossScenario::class);
    }
}
