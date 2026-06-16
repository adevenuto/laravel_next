<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'display_name',
        'hometown',
        'xp',
        'vocab_counter',
        'streak_count',
        'streak_last_active_date',
        'progress_snapshot',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'streak_last_active_date' => 'date',
            'xp' => 'integer',
            'vocab_counter' => 'integer',
            'streak_count' => 'integer',
            'progress_snapshot' => 'array',
        ];
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(UserLessonProgress::class);
    }

    public function skillMastery(): HasMany
    {
        return $this->hasMany(UserSkillMastery::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(UserCollection::class);
    }

    public function bossAttempts(): HasMany
    {
        return $this->hasMany(UserBossAttempt::class);
    }
}
