<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialogueLine extends Model
{
    protected $fillable = ['audio_key', 'es', 'en'];
}
