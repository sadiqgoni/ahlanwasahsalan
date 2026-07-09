<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opening_float' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'counted_cash' => 'decimal:2',
        'variance' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    public static function currentFor(int $userId): ?self
    {
        return static::where('user_id', $userId)->whereNull('closed_at')->latest('opened_at')->first();
    }

    public function cashSalesTotal(): float
    {
        return (float) $this->sales()->where('status', 'completed')->where('payment_method', 'cash')->sum('total');
    }

    public function salesTotalByMethod(string $method): float
    {
        return (float) $this->sales()->where('status', 'completed')->where('payment_method', $method)->sum('total');
    }
}
