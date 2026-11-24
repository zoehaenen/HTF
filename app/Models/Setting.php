<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['weekday', 'hours'];

    protected $casts = ['hours' => 'float'];
}
