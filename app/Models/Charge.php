<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Charge extends Model
{
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    protected $guarded = [];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('pos.charges'));
        static::deleted(fn () => Cache::forget('pos.charges'));
    }

    /** Section this charge applies to — null means every section. */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Amount this charge adds on top of the given base (₦ subtotal of the lines it covers). */
    public function amountFor(float $base): float
    {
        if ($base <= 0) {
            return 0.0;
        }

        return $this->type === self::TYPE_PERCENT
            ? round($base * (float) $this->rate / 100, 2)
            : (float) $this->rate;
    }
}
