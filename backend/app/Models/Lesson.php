<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = ['unit_id', 'slug', 'title', 'order', 'skill_key', 'teach_screens'];

    protected $casts = [
        'teach_screens' => 'array',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class)->orderBy('order');
    }

    public function progressFor(User $user): ?UserLessonProgress
    {
        return $this->hasMany(UserLessonProgress::class)
            ->where('user_id', $user->id)
            ->first();
    }
}
