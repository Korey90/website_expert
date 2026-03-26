<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalculatorStep extends Model
{
    protected $fillable = [
        'step_number',
        'question_en', 'question_pl', 'question_pt',
        'hint_en',     'hint_pl',     'hint_pt',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
