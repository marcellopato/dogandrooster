<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'idempotency_key',
        'quote_id',
        'status',
        'total_cents',
        'payment_intent_id',
    ];

    protected $casts = [
        'order_id' => 'string',
        'quote_id' => 'string',
        'total_cents' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (! $order->order_id) {
                $order->order_id = Str::uuid()->toString();
            }

            if (! $order->payment_intent_id) {
                $order->payment_intent_id = 'pi_'.Str::random(20);
            }
        });
    }

    /**
     * Get the quote this order is based on
     */
    public function priceQuote(): BelongsTo
    {
        return $this->belongsTo(PriceQuote::class, 'quote_id', 'quote_id');
    }

    /**
     * Get the order lines for this order
     */
    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /**
     * Calculate total from order lines
     */
    public function calculateTotal(): int
    {
        return $this->orderLines()->sum('subtotal_cents');
    }

    /**
     * Check if order can transition to status
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['authorized', 'failed'],
            'authorized' => ['captured', 'failed'],
            'captured' => [],
            'failed' => [],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }

    /**
     * Transition order to new status
     */
    public function transitionTo(string $newStatus): bool
    {
        if (! $this->canTransitionTo($newStatus)) {
            return false;
        }

        $this->update(['status' => $newStatus]);

        return true;
    }

    /**
     * Create order from quote with idempotency
     */
    public static function createFromQuote(
        PriceQuote $quote,
        string $idempotencyKey
    ): self {
        // Check for existing order with same idempotency key
        $existingOrder = self::where('idempotency_key', $idempotencyKey)->first();

        if ($existingOrder) {
            return $existingOrder;
        }

        // Create new order
        $order = self::create([
            'quote_id' => $quote->quote_id,
            'idempotency_key' => $idempotencyKey,
            'status' => 'pending',
            'total_cents' => $quote->total_price_cents,
        ]);

        // Create order line
        OrderLine::create([
            'order_id' => $order->id,
            'sku' => $quote->sku,
            'quantity' => $quote->quantity,
            'unit_price_cents' => $quote->unit_price_cents,
            'subtotal_cents' => $quote->total_price_cents,
        ]);

        return $order;
    }
}
