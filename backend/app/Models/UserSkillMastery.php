<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkillMastery extends Model
{
    protected $table = 'user_skill_mastery';

    protected $fillable = [
        'user_id',
        'skill_key',
        'tier',
        'srs_due_at',
        'srs_interval_days',
        'srs_ease',
    ];

    protected $casts = [
        'srs_due_at' => 'datetime',
        'srs_interval_days' => 'integer',
        'srs_ease' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
