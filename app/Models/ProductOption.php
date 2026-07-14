<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class ProductOption extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('pos.menu'));
        static::deleted(fn () => Cache::forget('pos.menu'));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
