<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'language', 'content', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function forLanguage(string $language = 'en'): \Illuminate\Support\Collection
    {
        return self::where('language', $language)
            ->where('is_active', true)
            ->orderBy('type')
            ->get(['id', 'name', 'type']);
    }
}
