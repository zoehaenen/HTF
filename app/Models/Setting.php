<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;

    protected $fillable = ['weekday', 'hours'];

    protected $casts = ['weekday' => 'integer', 'hours' => 'float'];

    public static function available(): array
    {
        $hours = static::pluck('hours', 'weekday')->all();
        $available = [];
        foreach (range(0, 6) as $w) {
            $available[$w] = (float) ($hours[$w] ?? 2);
        }

        return $available;
    }
}
