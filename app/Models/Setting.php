<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['setting_key', 'setting_value'];

    protected static array $cache = [];

    /**
     * Read a setting value by key, cached per request.
     */
    public static function value(string $key, string $default = ''): string
    {
        if (array_key_exists($key, static::$cache)) {
            return static::$cache[$key];
        }

        $value = static::query()
            ->where('setting_key', $key)
            ->value('setting_value');

        return static::$cache[$key] = trim((string) ($value ?? $default));
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