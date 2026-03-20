<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing  = false;
    protected $keyType    = 'string';
    protected $fillable   = ['key', 'value', 'group'];

    /** Pobierz wartość z cache (1 dzień TTL). */
    public static function get(string $key, mixed $default = null): mixed
    {
        return cache()->remember("settings.{$key}", now()->addDay(), function () use ($key, $default) {
            return static::find($key)?->value ?? $default;
        });
    }

    /** Zapisz wartość i wyczyść cache. */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        cache()->forget("settings.{$key}");
    }
}
