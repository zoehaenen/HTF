<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Task extends Model
{
    protected $fillable = ['title', 'course', 'deadline', 'estimated_hours', 'done', 'source_text'];

    protected $casts = [
        'deadline' => 'date',
        'estimated_hours' => 'float',
        'done' => 'boolean',
    ];
}
