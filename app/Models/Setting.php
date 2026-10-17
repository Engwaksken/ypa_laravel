<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['setting_key', 'setting_value'];

    /**
     * The legacy settings table is a plain key/value store without
     * timestamp columns.
     */
    public $timestamps = false;

    protected static function booted(): void
    {
        static::saved(fn () => app('settings.values')->exchangeArray([]));
        static::deleted(fn () => app('settings.values')->exchangeArray([]));
    }

    /**
     * Read a setting value by key, cached per request.
     */
    public static function value(string $key, string $default = ''): string
    {
        $cache = app('settings.values');
        if (!$cache->offsetExists('values')) {
            $cache['values'] = static::query()->pluck('setting_value', 'setting_key')->all();
        }

        return trim((string) ($cache['values'][$key] ?? $default));
    }

    /**
     * Read a setting that holds a relative asset path and normalise it
     * (strips leading "../" and "./" like the legacy asset_url() helper).
     */
    public static function asset(string $key, string $default = ''): string
    {
        $path = str_replace('\\', '/', trim(static::value($key, $default)));
        $path = preg_replace('#^\.\./#', '', $path);
        $path = preg_replace('#^\./#', '', $path);

        return ltrim($path, '/');
    }
}
