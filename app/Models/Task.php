<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['title', 'course', 'deadline', 'estimated_hours', 'done', 'source_text'];

    protected $casts = [
        'deadline' => 'date:Y-m-d',
        'estimated_hours' => 'float',
        'done' => 'boolean',
    ];
}
