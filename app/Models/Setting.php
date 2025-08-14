<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    const TYPES = [
        'string' => 'string',
        'integer' => 'integer',
        'boolean' => 'boolean',
        'json' => 'json',
        'text' => 'text',
    ];

    const GROUPS = [
        'general' => 'general',
        'security' => 'security',
        'email' => 'email',
        'charter' => 'charter',
        'audit' => 'audit',
        'mfa' => 'mfa',
    ];

    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            return self::castValue($setting->value, $setting->type);
        });
    }

    public static function set(string $key, $value, string $type = 'string', string $group = 'general'): self
    {
        Cache::forget("setting_{$key}");
        
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => self::prepareValue($value, $type),
                'type' => $type,
                'group' => $group,
            ]
        );
    }

    public static function getByGroup(string $group): array
    {
        return Cache::remember("settings_group_{$group}", 3600, function () use ($group) {
            return self::where('group', $group)
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => self::castValue($setting->value, $setting->type)];
                })
                ->toArray();
        });
    }

    public static function clearCache(): void
    {
        Cache::flush();
    }

    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    protected static function prepareValue($value, string $type): string
    {
        return match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    protected static function boot()
    {
        parent::boot();
        
        static::saved(function ($setting) {
            Cache::forget("setting_{$setting->key}");
            Cache::forget("settings_group_{$setting->group}");
        });
        
        static::deleted(function ($setting) {
            Cache::forget("setting_{$setting->key}");
            Cache::forget("settings_group_{$setting->group}");
        });
    }
}