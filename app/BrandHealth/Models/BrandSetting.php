<?php

namespace App\BrandHealth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BrandSetting extends Model
{
    protected $table = 'brand_settings';

    protected $fillable = ['key', 'value'];

    public const MAINTENANCE_MODE = 'maintenance_mode';

    private const CACHE_KEY = 'brand_settings.all';

    /**
     * All settings as a flat key => value map, cached — read on nearly every
     * request (the maintenance-mode check), so this avoids a query per request.
     */
    public static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::allCached()[$key] ?? $default;
    }

    public static function isMaintenanceMode(): bool
    {
        return self::get(self::MAINTENANCE_MODE, '0') === '1';
    }

    public static function set(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }
}
