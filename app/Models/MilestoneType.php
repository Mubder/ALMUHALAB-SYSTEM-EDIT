<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MilestoneType extends Model
{
    protected $fillable = ['key', 'label', 'icon', 'color', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    const COLORS = [
        'primary'   => 'Blue',
        'success'   => 'Green',
        'warning'   => 'Yellow',
        'danger'    => 'Red',
        'info'      => 'Cyan',
        'secondary' => 'Grey',
    ];

    const CACHE_KEY = 'milestone_types_all';

    public static function allActive(): \Illuminate\Support\Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, fn() =>
            static::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function asOptions(): array
    {
        return static::allActive()
            ->mapWithKeys(fn($m) => [$m->key => [
                'label' => $m->label,
                'icon'  => $m->icon,
                'color' => $m->color,
            ]])
            ->all();
    }

    protected static function booted(): void
    {
        static::saved(fn() => static::clearCache());
        static::deleted(fn() => static::clearCache());
    }
}
