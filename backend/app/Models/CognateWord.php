<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CognateWord extends Model
{
    protected $fillable = [
        'en',
        'es',
        'rule_key',
        'is_exception',
        'exception_note',
        'audio_key',
    ];

    protected $casts = [
        'is_exception' => 'boolean',
    ];
}
