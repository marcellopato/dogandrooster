<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'sku',
        'quantity',
        'unit_price_cents',
        'subtotal_cents',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'quantity' => 'integer',
        'unit_price_cents' => 'integer',
        'subtotal_cents' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($orderLine) {
            // Auto-calculate subtotal if not provided
            if (! $orderLine->subtotal_cents) {
                $orderLine->subtotal_cents = $orderLine->quantity * $orderLine->unit_price_cents;
            }
        });
    }

    /**
     * Get the order this line belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product for this line
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'sku', 'sku');
    }

    /**
     * Verify subtotal integrity
     */
    public function verifySubtotal(): bool
    {
        $expectedSubtotal = $this->quantity * $this->unit_price_cents;

        return $this->subtotal_cents === $expectedSubtotal;
    }
}
