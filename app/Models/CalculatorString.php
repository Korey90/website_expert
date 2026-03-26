<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalculatorString extends Model
{
    protected $fillable = [
        'key', 'group', 'value_en', 'value_pl', 'value_pt', 'note', 'sort_order',
    ];
}
