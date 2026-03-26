<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type', 'label', 'description'];

    // ─── Static helpers ───────────────────────────────────────────────────────

    /**
     * Get a setting value by key with an optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->typed_value : $default;
        });
    }

    /**
     * Set a setting value and bust the cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }

    /**
     * Return all settings in a group as key => value map.
     */
    public static function group(string $group): array
    {
        return Cache::rememberForever("settings:group:{$group}", function () use ($group) {
            return static::where('group', $group)
                ->get()
                ->mapWithKeys(fn($s) => [$s->key => $s->typed_value])
                ->toArray();
        });
    }

    // ─── Accessor ─────────────────────────────────────────────────────────────

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'json'    => json_decode($this->value, true),
            default   => $this->value,
        };
    }
}
