<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Reads effective platform settings: a value persisted in the settings table
 * overrides the corresponding config('jiwa...') default. Write through
 * PlatformSettings::set() from the admin Settings page.
 */
class PlatformSettings
{
    protected static ?Collection $cache = null;

    /**
     * Get the effective value for a setting key.
     *
     * @param  mixed  $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all()->get($key);

        return $value === null ? $default : $value;
    }

    /**
     * Persist a value (stringified) for a setting key.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        $value = match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => json_encode($value),
        };

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group],
        );

        self::$cache = null;
    }

    /**
     * All persisted settings keyed by key, each cast to its natural type
     * (bool / int / float / string / array).
     */
    public static function all(): Collection
    {
        if (self::$cache === null) {
            self::$cache = Setting::pluck('value', 'key')->map(fn ($value) => self::cast($value));
        }

        return self::$cache;
    }

    public static function flushCache(): void
    {
        self::$cache = null;
    }

    public static function cast(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $json = json_decode((string) $value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }

        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        if (in_array(strtolower($value), ['true', 'false'], true)) {
            return strtolower($value) === 'true';
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }

    /**
     * Convenience accessor for the config-style dotted keys, e.g.
     * PlatformSettings::config('jiwa.min_investment').
     */
    public static function config(string $key): mixed
    {
        $settingKey = str_replace('jiwa.', '', $key);
        $default = config($key);

        return self::get($settingKey, $default);
    }
}
