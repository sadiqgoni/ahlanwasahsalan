<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // The POS renders the menu from cache — any menu edit must invalidate it.
        static::saved(fn () => Cache::forget('pos.menu'));
        static::deleted(fn () => Cache::forget('pos.menu'));
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('sort');
    }
}
