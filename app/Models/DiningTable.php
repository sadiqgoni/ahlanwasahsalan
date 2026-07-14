<?php

namespace App\Models;

use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Output\QROutputInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DiningTable extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (DiningTable $table) {
            $table->qr_token ??= static::generateToken();
        });
    }

    public function customerOrders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::lower(Str::random(20));
        } while (static::where('qr_token', $token)->exists());

        return $token;
    }

    public function orderUrl(): string
    {
        return route('table.menu', $this->qr_token);
    }

    /** Inline SVG markup for the QR code pointing at this table's ordering link. */
    public function qrSvg(): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'imageBase64' => false,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
        ]);

        $svg = (new QRCode($options))->render($this->orderUrl());

        // Strip the XML prolog — this is embedded inline in an HTML page, not standalone.
        return trim(preg_replace('/^<\?xml.*?\?>/s', '', $svg));
    }

    /** Accepted order items for this table not yet rung up at the POS — the customer's open tab. */
    public function openTabItems()
    {
        return CustomerOrderItem::query()
            ->whereNull('sale_item_id')
            ->whereHas('customerOrder', fn ($q) => $q
                ->where('dining_table_id', $this->id)
                ->where('status', CustomerOrder::STATUS_ACCEPTED))
            ->with(['customerOrder', 'product'])
            ->get();
    }

    public function openTabTotal(): float
    {
        return (float) $this->openTabItems()->sum('line_total');
    }

    public function pendingOrdersCount(): int
    {
        return $this->customerOrders()->where('status', CustomerOrder::STATUS_PENDING)->count();
    }
}
